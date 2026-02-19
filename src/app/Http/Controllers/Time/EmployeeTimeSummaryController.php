<?php

namespace App\Http\Controllers\Time;

use App\Http\Controllers\Controller;
use App\Models\GlobalDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Models\Project;
use Carbon\Carbon;

class EmployeeTimeSummaryController extends Controller
{
    // ================================================================
    //  SCOPE FIELD MAPPING
    // ================================================================

    /**
     * Sub-projects classified under the "Other Time Off (200)" category.
     */
    private const OTHER_TO_SUB_PROJECTS = [
        'Parental Leave',
        'Jury Duty',
        'Funeral',
        'Bereavement',
        'FMLA',
        'UTO',
    ];

    /**
     * Project codes that map to named NB Level-2 categories.
     * Used to ensure these are excluded from the "Other NB" catch-all
     * when computed via remainder math.
     */
    private const NB_NAMED_PROJECT_CODES = [
        'CEGTRNG',  // Training
        'CEGMKTG',  // Marketing
        'CEGEDU',   // Education
    ];

    // ================================================================
    //  INDEX
    // ================================================================

    /**
     * Display the Summary timesheet page with initial data.
     */
    public function index()
    {
        try {
            $indexData = $this->getPayPeriodIndexData();

            return view('time.employee-time-summary', $indexData);

        } catch (\Exception $e) {
            Log::error('EmployeeTimeSummaryController index error: ' . $e->getMessage());
            return response()->view('errors.500', ['error_message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch and format pay period data for the initial page load.
     */
    private function getPayPeriodIndexData(): array
    {
        $doc = GlobalDoc::where('name', 'Pay-Periods')->first();
        $payPeriods = $doc->{'Pay-Periods'};

        $payPeriodsFormatted = [];
        $earliestStart = null;
        $latestEnd = null;

        foreach ($payPeriods as $year => $periods) {
            $cleanedPeriods = [];
            foreach ($periods as $period) {
                $start = $period['start_date'];
                $end = $period['end_date'];

                if ($earliestStart === null || $start->lt($earliestStart)) {
                    $earliestStart = $start;
                }
                if ($latestEnd === null || $end->gt($latestEnd)) {
                    $latestEnd = $end;
                }

                $cleanedPeriods[] = [
                    'start_date' => $start->format('m/d/y H:i:s'),
                    'end_date'   => $end->format('m/d/y H:i:s'),
                ];
            }
            $payPeriodsFormatted[$year] = $cleanedPeriods;
        }

        $startDateCutOff = $earliestStart ? $earliestStart->format('m/d/y H:i:s') : null;
        $endDateCutOff = $latestEnd ? $latestEnd->format('m/d/y H:i:s') : null;

        return [
            'payPeriodData'   => $payPeriodsFormatted,
            'startDateCutOff' => $startDateCutOff,
            'endDateCutOff'   => $endDateCutOff,
        ];
    }

    // ================================================================
    //  GET DATA
    // ================================================================

    /**
     * Fetch data for the Summary display component.
     * Called via POST from the data-bridge.
     */
    public function getData(Request $request)
    {
        $componentKeys = ['summaryTable'];

        // ----------------------------------------------------------
        //  STAGE 1: Global Validation & Parsing
        // ----------------------------------------------------------
        try {
            $parsed = $this->parseRequest($request);
        } catch (\Exception $e) {
            Log::error('EmployeeTimeSummary getData validation error: ' . $e->getMessage(), [
                'inputs' => $request->all(),
                'trace'  => $e->getTraceAsString(),
            ]);

            $errorResponse = [];
            foreach ($componentKeys as $key) {
                $errorResponse[$key] = ApiResponse::error('Validation failed: ' . $e->getMessage());
            }
            return response()->json($errorResponse, 200);
        }

        // ----------------------------------------------------------
        //  STAGE 2: Shared Data Gathering
        // ----------------------------------------------------------
        $pipelineResult = null;
        try {
            $pipelineResult = $this->runSummaryPipeline(
                $parsed['userEmail'],
                $parsed['queryStart'],
                $parsed['queryEnd'],
                $parsed['granularity'],
                $parsed['periods'],
                $parsed['nonBillableCodes'],
                $parsed['wageType'],
            );
        } catch (\Exception $e) {
            Log::error('EmployeeTimeSummary pipeline error: ' . $e->getMessage(), [
                'inputs' => $request->all(),
                'trace'  => $e->getTraceAsString(),
            ]);
            $pipelineResult = ApiResponse::error('Failed to query summary data.');
        }

        // ----------------------------------------------------------
        //  STAGE 3: Individual Component Processing
        // ----------------------------------------------------------
        $summaryResult = $this->buildSummaryComponent(
            $pipelineResult,
            $parsed['periods'],
            $parsed['periodHeaders'],
            $parsed['granularity'],
        );

        return response()->json([
            'summaryTable' => $summaryResult,
            'granularity'  => $parsed['granularity'],
        ], 200);
    }

    // ================================================================
    //  GET DATA HELPERS — Validation & Parsing
    // ================================================================

    /**
     * Parse and validate the incoming request for the Summary page.
     *
     * @throws \Exception on validation failure
     */
    private function parseRequest(Request $request): array
    {
        $common = $this->parseCommonRequest($request);

        // --- Fetch Non-Billable Project Codes (Summary-specific) ---
        $nonBillableCodes = Project::where('is_internal', true)
            ->pluck('projectcode')
            ->toArray();

        // --- Fetch User Wage Type (for overtime calculation) ---
        //$wageType = Auth::user()->wage_type ?? 'salary'; // TODO update
        $wageType = "salaried";

        return array_merge($common, [
            'nonBillableCodes' => $nonBillableCodes,
            'wageType'         => $wageType,
        ]);
    }

    /**
     * Parse and validate the common parts of an incoming getData request.
     *
     * @throws \Exception on validation failure
     */
    private function parseCommonRequest(Request $request): array
    {
        $granularity = $request->input('granularity');
        $validGranularities = ['day', 'pay-period', 'month', 'quarter', 'year'];
        if (!in_array($granularity, $validGranularities, true)) {
            throw new \Exception("Invalid granularity: {$granularity}");
        }

        // --- Parse Date Range ---
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        if ($granularity === 'day') {
            $queryStart = Carbon::parse($startInput)->startOfDay();
            $queryEnd = Carbon::parse($endInput)->endOfDay();
        } else {
            $queryStart = Carbon::parse($startInput['startDate'])->startOfDay();
            $queryEnd = Carbon::parse($endInput['endDate'])->endOfDay();
        }

        if ($queryStart->gt($queryEnd)) {
            throw new \Exception('Start date must be before or equal to end date.');
        }

        // --- Fetch Pay Periods (needed for pay-period granularity) ---
        $payPeriods = null;
        if ($granularity === 'pay-period') {
            $payPeriods = $this->getRelevantPayPeriods($queryStart, $queryEnd);
            if (empty($payPeriods)) {
                throw new \Exception('No pay periods found for the selected date range.');
            }
        }

        // --- Generate Period Definitions ---
        $periods = $this->generatePeriods($granularity, $queryStart, $queryEnd, $payPeriods);

        // --- Generate Smart Column Headers ---
        $periodHeaders = $this->generatePeriodHeaders($granularity, $periods, $queryStart, $queryEnd);

        // --- Determine Header Mode ---
        $headerMode = 'standard';
        if ($granularity === 'pay-period') {
            $headerMode = 'compact-tall';
        } elseif ($granularity === 'day' && $queryStart->year !== $queryEnd->year) {
            $headerMode = 'compact-short';
        }

        // --- Resolve User ---
        //$userEmail = Auth::user()->email;
        $userEmail = "speichel@ceg-engineers.com";

        return [
            'granularity'   => $granularity,
            'queryStart'    => $queryStart,
            'queryEnd'      => $queryEnd,
            'periods'       => $periods,
            'periodHeaders' => $periodHeaders,
            'headerMode'    => $headerMode,
            'payPeriods'    => $payPeriods,
            'userEmail'     => $userEmail,
        ];
    }

    /**
     * Fetch pay periods from the globals collection that overlap with
     * the given date range. Returns an array sorted by start date.
     */
    private function getRelevantPayPeriods(Carbon $queryStart, Carbon $queryEnd): array
    {
        $doc = GlobalDoc::where('name', 'Pay-Periods')->first();
        $allPayPeriods = $doc->{'Pay-Periods'};

        $relevant = [];
        $seen = [];

        foreach ($allPayPeriods as $year => $periods) {
            foreach ($periods as $period) {
                $ppStart = $period['start_date'];
                $ppEnd = $period['end_date'];

                $uniqueKey = $ppStart->format('Y-m-d');

                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                if ($ppEnd->gte($queryStart) && $ppStart->lte($queryEnd)) {
                    $relevant[] = [
                        'start' => $ppStart->copy(),
                        'end'   => $ppEnd->copy(),
                    ];
                    $seen[$uniqueKey] = true;
                }
            }
        }

        usort($relevant, fn($a, $b) => $a['start']->timestamp - $b['start']->timestamp);

        return $relevant;
    }

    /**
     * Generate an ordered array of period definitions based on granularity.
     * Each period has: key (sortable string), start (Carbon), end (Carbon).
     */
    private function generatePeriods(
        string $granularity,
        Carbon $queryStart,
        Carbon $queryEnd,
        ?array $payPeriods = null
    ): array {
        $periods = [];

        switch ($granularity) {
            case 'day':
                $current = $queryStart->copy()->startOfDay();
                $end = $queryEnd->copy()->startOfDay();
                while ($current->lte($end)) {
                    $periods[] = [
                        'key'   => $current->format('Y-m-d'),
                        'start' => $current->copy(),
                        'end'   => $current->copy()->endOfDay(),
                    ];
                    $current->addDay();
                }
                break;

            case 'pay-period':
                foreach ($payPeriods as $pp) {
                    $periods[] = [
                        'key'   => $pp['start']->format('Y-m-d'),
                        'start' => $pp['start']->copy(),
                        'end'   => $pp['end']->copy(),
                    ];
                }
                break;

            case 'month':
                $current = $queryStart->copy()->startOfMonth();
                $end = $queryEnd->copy()->startOfMonth();
                while ($current->lte($end)) {
                    $periods[] = [
                        'key'   => $current->format('Y-m'),
                        'start' => $current->copy()->startOfMonth(),
                        'end'   => $current->copy()->endOfMonth(),
                    ];
                    $current->addMonth();
                }
                break;

            case 'quarter':
                $current = $queryStart->copy()->firstOfQuarter();
                $end = $queryEnd->copy()->firstOfQuarter();
                while ($current->lte($end)) {
                    $q = (int) ceil($current->month / 3);
                    $periods[] = [
                        'key'   => $current->format('Y') . '-Q' . $q,
                        'start' => $current->copy()->firstOfQuarter(),
                        'end'   => $current->copy()->lastOfQuarter()->endOfDay(),
                    ];
                    $current->addMonths(3);
                }
                break;

            case 'year':
                $current = $queryStart->copy()->startOfYear();
                $end = $queryEnd->copy()->startOfYear();
                while ($current->lte($end)) {
                    $periods[] = [
                        'key'   => $current->format('Y'),
                        'start' => $current->copy()->startOfYear(),
                        'end'   => $current->copy()->endOfYear(),
                    ];
                    $current->addYear();
                }
                break;
        }

        return $periods;
    }

    /**
     * Generate the smart, compact column header labels for each period.
     * Returns an associative array: period_key => display_label.
     */
    private function generatePeriodHeaders(
        string $granularity,
        array $periods,
        Carbon $queryStart,
        Carbon $queryEnd
    ): array {
        $headers = [];

        switch ($granularity) {
            case 'day':
                $sameMonth = $queryStart->month === $queryEnd->month
                    && $queryStart->year === $queryEnd->year;
                $sameYear = $queryStart->year === $queryEnd->year;

                foreach ($periods as $p) {
                    if ($sameMonth) {
                        $headers[$p['key']] = $p['start']->format('d');
                    } elseif ($sameYear) {
                        $headers[$p['key']] = $p['start']->format('M d');
                    } else {
                        $headers[$p['key']] = $p['start']->format('m-d-y');
                    }
                }
                break;

            case 'pay-period':
                foreach ($periods as $p) {
                    $headers[$p['key']] = $p['start']->format('m-d-y')
                        . ' - '
                        . $p['end']->format('m-d-y');
                }
                break;

            case 'month':
                $sameYear = $queryStart->year === $queryEnd->year;
                foreach ($periods as $p) {
                    $headers[$p['key']] = $sameYear
                        ? $p['start']->format('M')
                        : $p['start']->format('M Y');
                }
                break;

            case 'quarter':
                foreach ($periods as $p) {
                    $q = (int) ceil($p['start']->month / 3);
                    $headers[$p['key']] = 'Q' . $q . ' ' . $p['start']->format('Y');
                }
                break;

            case 'year':
                foreach ($periods as $p) {
                    $headers[$p['key']] = $p['start']->format('Y');
                }
                break;
        }

        return $headers;
    }

    // ================================================================
    //  GET DATA HELPERS — Pipeline Builder
    // ================================================================

    /**
     * Build and run the Summary aggregation pipeline using $facet
     * to retrieve both the per-period summary and the per-project
     * billable breakdown in a single database round-trip.
     *
     * Returns the raw $facet result: a single document with
     * 'summary' and 'billable_detail' arrays.
     */
    private function runSummaryPipeline(
        string $userEmail,
        Carbon $queryStart,
        Carbon $queryEnd,
        string $granularity,
        array $periods,
        array $nonBillableCodes,
        string $wageType,
    ): array {
        $pipeline = [];

        // --- Match Stage ---
        $pipeline[] = [
            '$match' => [
                'user_email' => $userEmail,
                'date' => [
                    '$gte' => new \MongoDB\BSON\UTCDateTime($queryStart->getTimestamp() * 1000),
                    '$lte' => new \MongoDB\BSON\UTCDateTime($queryEnd->getTimestamp() * 1000),
                ],
            ],
        ];

        // --- Period Key Assignment (before $facet so both branches have it) ---
        if ($granularity === 'pay-period') {
            $ppStages = $this->getPayPeriodKeyStages($periods);
            $pipeline = array_merge($pipeline, $ppStages);
        } else {
            $pipeline[] = [
                '$addFields' => [
                    'period_key' => $this->getPeriodKeyExpression($granularity),
                ],
            ];
        }

        // --- $facet: summary + billable_detail ---
        $pipeline[] = [
            '$facet' => [
                'summary'         => $this->buildSummaryFacetBranch($nonBillableCodes, $wageType, $granularity),
                'billable_detail' => $this->buildBillableDetailFacetBranch($nonBillableCodes),
            ],
        ];

        // --- Execute ---
        $collection = DB::connection('mongodb')->getCollection('hours');
        $cursor = $collection->aggregate($pipeline);
        $results = iterator_to_array($cursor);

        // $facet always returns exactly one document.
        // The MongoDB driver returns BSONDocument / BSONArray objects,
        // so we recursively convert to plain PHP arrays for downstream use.
        $facetDoc = $results[0] ?? [];

        $toArray = function ($value) {
            if ($value instanceof \MongoDB\Model\BSONArray || $value instanceof \MongoDB\Model\BSONDocument) {
                return json_decode(json_encode($value), true);
            }
            return (array) $value;
        };

        return [
            'summary'         => $toArray($facetDoc['summary'] ?? []),
            'billable_detail' => $toArray($facetDoc['billable_detail'] ?? []),
        ];
    }

    /**
     * Build the "summary" sub-pipeline for the $facet stage.
     * Groups by period_key and computes all NB category accumulators
     * at max detail (Level 2), plus billable total and total hours.
     */
    private function buildSummaryFacetBranch(
        array $nonBillableCodes,
        string $wageType,
        string $granularity,
    ): array {
        $branch = [];

        // --- Group: compute all named NB categories + billable + total ---
        $groupStage = [
            '_id' => '$period_key',

            'pto' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$and' => [
                            ['$eq' => ['$project_code', 'CEG']],
                            ['$eq' => ['$sub_project', 'PTO']],
                        ]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'holiday' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$and' => [
                            ['$eq' => ['$project_code', 'CEG']],
                            ['$eq' => ['$sub_project', 'Holiday']],
                        ]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'other_to' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$and' => [
                            ['$eq' => ['$project_code', 'CEG']],
                            ['$in' => ['$sub_project', self::OTHER_TO_SUB_PROJECTS]],
                        ]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'meetings' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$and' => [
                            ['$eq' => ['$project_code', 'CEG']],
                            ['$eq' => ['$sub_project', 'Staff Meetings and HR']],
                        ]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'training' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$eq' => ['$project_code', 'CEGTRNG']],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'marketing' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$eq' => ['$project_code', 'CEGMKTG']],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'education' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$eq' => ['$project_code', 'CEGEDU']],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'total_nb' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$in' => ['$project_code', $nonBillableCodes]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'billable' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$not' => [['$in' => ['$project_code', $nonBillableCodes]]]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'total_hours' => ['$sum' => '$hours'],
        ];

        $branch[] = ['$group' => $groupStage];

        // --- Project: pass through + compute derived fields ---
        $projectStage = [
            '_id'         => 0,
            'period_key'  => '$_id',
            'pto'         => 1,
            'holiday'     => 1,
            'other_to'    => 1,
            'meetings'    => 1,
            'training'    => 1,
            'marketing'   => 1,
            'education'   => 1,
            'total_nb'    => 1,
            'billable'    => 1,
            'total_hours' => 1,
            'billable_percentage' => [
                '$cond' => [
                    'if'   => ['$gt' => ['$total_hours', 0]],
                    'then' => [
                        '$multiply' => [
                            ['$divide' => ['$billable', '$total_hours']],
                            100,
                        ],
                    ],
                    'else' => 0,
                ],
            ],
        ];

        // Overtime: only for pay-period granularity
        if ($granularity === 'pay-period') {
            if ($wageType === 'hourly') {
                $projectStage['overtime'] = [
                    '$cond' => [
                        'if'   => ['$gt' => ['$total_hours', 80]],
                        'then' => ['$subtract' => ['$total_hours', 80]],
                        'else' => 0,
                    ],
                ];
            } else {
                $projectStage['overtime'] = ['$literal' => 0];
            }
        }

        $branch[] = ['$project' => $projectStage];

        // --- Sort chronologically ---
        $branch[] = ['$sort' => ['period_key' => 1]];

        return $branch;
    }

    /**
     * Build the "billable_detail" sub-pipeline for the $facet stage.
     * Filters to billable entries only, then groups by period + project_code.
     */
    private function buildBillableDetailFacetBranch(array $nonBillableCodes): array
    {
        return [
            [
                '$match' => [
                    '$expr' => [
                        '$not' => [['$in' => ['$project_code', $nonBillableCodes]]],
                    ],
                ],
            ],
            [
                '$group' => [
                    '_id' => [
                        'period'  => '$period_key',
                        'project' => '$project_code',
                    ],
                    'hours' => ['$sum' => '$hours'],
                ],
            ],
        ];
    }

    /**
     * Returns the MongoDB $addFields expression for computing period_key
     * based on the given (non-pay-period) granularity.
     */
    private function getPeriodKeyExpression(string $granularity): array
    {
        return match ($granularity) {
            'day' => [
                '$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$date'],
            ],
            'month' => [
                '$dateToString' => ['format' => '%Y-%m', 'date' => '$date'],
            ],
            'quarter' => [
                '$concat' => [
                    ['$toString' => ['$year' => '$date']],
                    '-Q',
                    ['$toString' => ['$ceil' => ['$divide' => [['$month' => '$date'], 3]]]],
                ],
            ],
            'year' => [
                '$toString' => ['$year' => '$date'],
            ],
            default => throw new \Exception("Unsupported granularity for period key: {$granularity}"),
        };
    }

    /**
     * Returns the pipeline stages needed to assign a period_key for
     * pay-period granularity.
     */
    private function getPayPeriodKeyStages(array $periods): array
    {
        $ppArray = [];
        foreach ($periods as $p) {
            $ppArray[] = [
                'start_date' => new \MongoDB\BSON\UTCDateTime($p['start']->getTimestamp() * 1000),
                'end_date'   => new \MongoDB\BSON\UTCDateTime($p['end']->getTimestamp() * 1000),
                'key'        => $p['key'],
            ];
        }

        return [
            [
                '$addFields' => [
                    '_matched_pp' => [
                        '$arrayElemAt' => [
                            [
                                '$filter' => [
                                    'input' => $ppArray,
                                    'as'    => 'pp',
                                    'cond'  => [
                                        '$and' => [
                                            ['$gte' => ['$date', '$$pp.start_date']],
                                            ['$lte' => ['$date', '$$pp.end_date']],
                                        ],
                                    ],
                                ],
                            ],
                            0,
                        ],
                    ],
                ],
            ],
            ['$match' => ['_matched_pp' => ['$ne' => null]]],
            ['$addFields' => ['period_key' => '$_matched_pp.key']],
            ['$project' => ['_matched_pp' => 0]],
        ];
    }

    // ================================================================
    //  GET DATA HELPERS — Component Result Processing
    // ================================================================

    /**
     * Process raw $facet pipeline results into the final ApiResponse.
     *
     * Responsibilities:
     *  1. Derive NB "other" fields at both detail levels (remainder math).
     *  2. Compute global top-5 billable project ranking.
     *  3. Pivot billable_detail into per-period top-N columns.
     *  4. Fill zero-rows for periods with no data.
     */
    private function buildSummaryComponent(
        $pipelineResult,
        array $periods,
        array $periodHeaders,
        string $granularity,
    ): object {
        // If shared data already carries an error, propagate it
        if ($pipelineResult instanceof \App\Http\Responses\ApiResponse) {
            return $pipelineResult;
        }

        try {
            $showOvertime = $granularity === 'pay-period';
            $summaryDocs = $pipelineResult['summary'] ?? [];
            $billableDetailDocs = $pipelineResult['billable_detail'] ?? [];

            // ----------------------------------------------------------
            //  A) Index summary results by period_key
            // ----------------------------------------------------------
            $summaryByKey = [];
            foreach ($summaryDocs as $doc) {
                $docArray = (array) $doc;
                $key = (string) ($docArray['period_key'] ?? '');
                if ($key !== '') {
                    $summaryByKey[$key] = $docArray;
                }
            }

            // ----------------------------------------------------------
            //  B) Compute global top-5 billable project ranking
            // ----------------------------------------------------------
            $topProjects = $this->computeTopBillableProjects($billableDetailDocs, 5);

            // Build a period → project → hours lookup from billable_detail
            $billableByPeriodProject = $this->pivotBillableDetail($billableDetailDocs);

            // Resolve project labels (code → display name)
            $topCodes = array_column($topProjects, 'code');
            $projectLabels = $this->resolveProjectLabels($topCodes);

            // Build the top projects metadata for the frontend
            $billableTopProjects = [];
            foreach ($topProjects as $idx => $proj) {
                $billableTopProjects[] = [
                    'rank'  => $idx + 1,
                    'code'  => $proj['code'],
                    'label' => $projectLabels[$proj['code']] ?? $proj['code'],
                ];
            }

            // ----------------------------------------------------------
            //  C) Build rows — one per period
            // ----------------------------------------------------------
            $rows = [];

            foreach ($periods as $p) {
                $key = $p['key'];
                $doc = $summaryByKey[$key] ?? null;

                if ($doc) {
                    $pto       = round((float) ($doc['pto'] ?? 0), 2);
                    $holiday   = round((float) ($doc['holiday'] ?? 0), 2);
                    $otherTo   = round((float) ($doc['other_to'] ?? 0), 2);
                    $meetings  = round((float) ($doc['meetings'] ?? 0), 2);
                    $training  = round((float) ($doc['training'] ?? 0), 2);
                    $marketing = round((float) ($doc['marketing'] ?? 0), 2);
                    $education = round((float) ($doc['education'] ?? 0), 2);
                    $totalNb   = round((float) ($doc['total_nb'] ?? 0), 2);
                    $billable  = round((float) ($doc['billable'] ?? 0), 2);
                    $totalHrs  = round((float) ($doc['total_hours'] ?? 0), 2);
                    $billPct   = round((float) ($doc['billable_percentage'] ?? 0), 1);

                    // NB "Other" via remainder math
                    $otherNbL2 = round($totalNb - ($pto + $holiday + $otherTo + $meetings + $training + $marketing + $education), 2);
                    $otherNbL1 = round($totalNb - ($pto + $holiday + $meetings), 2);

                    $row = [
                        'period_key'   => $key,
                        'period_label' => $periodHeaders[$key],

                        // NB Level 2 (max detail) — all named categories
                        'pto'        => $pto,
                        'holiday'    => $holiday,
                        'other_to'   => $otherTo,
                        'meetings'   => $meetings,
                        'training'   => $training,
                        'marketing'  => $marketing,
                        'education'  => $education,
                        'other_nb_l2' => $otherNbL2,
                        'total_nb'   => $totalNb,

                        // NB Level 1 derived "other" (training+marketing+education+other_to+catch-all)
                        'other_nb_l1' => $otherNbL1,

                        // Billable totals
                        'billable'            => $billable,
                        'total_hours'         => $totalHrs,
                        'billable_percentage' => $billPct,
                    ];

                    if ($showOvertime) {
                        $row['overtime'] = round((float) ($doc['overtime'] ?? 0), 2);
                    }
                } else {
                    // Zero-fill row for periods with no data
                    $row = [
                        'period_key'   => $key,
                        'period_label' => $periodHeaders[$key],
                        'pto' => 0, 'holiday' => 0, 'other_to' => 0,
                        'meetings' => 0, 'training' => 0, 'marketing' => 0, 'education' => 0,
                        'other_nb_l2' => 0, 'total_nb' => 0, 'other_nb_l1' => 0,
                        'billable' => 0, 'total_hours' => 0, 'billable_percentage' => 0,
                    ];
                    if ($showOvertime) {
                        $row['overtime'] = 0;
                    }
                }

                // --- Billable Top-N breakdown for this period ---
                $periodBillable = $billableByPeriodProject[$key] ?? [];
                $topHoursSum = 0;

                foreach ($topProjects as $idx => $proj) {
                    $hrs = round((float) ($periodBillable[$proj['code']] ?? 0), 2);
                    $row['top_' . ($idx + 1)] = $hrs;
                    $topHoursSum += $hrs;
                }

                // "Other Billable" = total billable − sum of top-5
                // (works for top-5 level; client derives top-3 other by adding top_4 + top_5 + other_billable)
                $row['other_billable'] = round($row['billable'] - $topHoursSum, 2);

                // Pad remaining top_N slots if fewer than 5 projects exist
                for ($i = count($topProjects) + 1; $i <= 5; $i++) {
                    $row['top_' . $i] = 0;
                }

                $rows[] = $row;
            }

            // ----------------------------------------------------------
            //  D) Return
            // ----------------------------------------------------------
            return ApiResponse::success([
                'rows'                => $rows,
                'showOvertime'        => $showOvertime,
                'billableTopProjects' => $billableTopProjects,
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeTimeSummary processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Unable to process Summary data.');
        }
    }

    // ================================================================
    //  GET DATA HELPERS — Billable Top-N Utilities
    // ================================================================

    /**
     * Compute the top-N billable projects by total hours across all periods.
     *
     * @param  array  $billableDetailDocs  Raw docs from the billable_detail facet branch.
     * @param  int    $n                   Number of top projects to return.
     * @return array  Ordered array of ['code' => string, 'total' => float], descending by total.
     */
    private function computeTopBillableProjects(array $billableDetailDocs, int $n): array
    {
        $globalTotals = [];

        foreach ($billableDetailDocs as $doc) {
            $docArray = (array) $doc;
            $id = (array) ($docArray['_id'] ?? []);
            $code = (string) ($id['project'] ?? '');
            $hours = (float) ($docArray['hours'] ?? 0);

            if ($code === '') {
                continue;
            }

            $globalTotals[$code] = ($globalTotals[$code] ?? 0) + $hours;
        }

        // Sort descending by total hours
        arsort($globalTotals);

        // Take top N
        $top = [];
        $count = 0;
        foreach ($globalTotals as $code => $total) {
            if ($count >= $n) break;
            $top[] = ['code' => $code, 'total' => $total];
            $count++;
        }

        return $top;
    }

    /**
     * Pivot billable_detail facet results into a nested lookup:
     *   period_key => project_code => hours
     */
    private function pivotBillableDetail(array $billableDetailDocs): array
    {
        $pivot = [];

        foreach ($billableDetailDocs as $doc) {
            $docArray = (array) $doc;
            $id = (array) ($docArray['_id'] ?? []);
            $period = (string) ($id['period'] ?? '');
            $project = (string) ($id['project'] ?? '');
            $hours = (float) ($docArray['hours'] ?? 0);

            if ($period === '' || $project === '') {
                continue;
            }

            $pivot[$period][$project] = $hours;
        }

        return $pivot;
    }

    /**
     * Resolve project codes to display labels via the Project model.
     * Returns an associative array: project_code => display_name.
     *
     * Falls back to the project code itself if no name is found.
     */
    private function resolveProjectLabels(array $projectCodes): array
    {
        if (empty($projectCodes)) {
            return [];
        }

        return Project::whereIn('projectcode', $projectCodes)
            ->pluck('name', 'projectcode')
            ->toArray();
    }
}