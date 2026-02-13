<?php

namespace App\Http\Controllers\Time;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Models\Hour;
use App\Models\GlobalDoc;
use App\Models\User;
use App\Models\Project;
use App\Models\SubProject;
use Carbon\Carbon;

class EmployeeTimeController extends Controller
{
    // ================================================================
    //  SCOPE FIELD MAPPING
    // ================================================================

    /**
     * Maps frontend scope identifiers to MongoDB document field names.
     */
    private const SCOPE_FIELD_MAP = [
        'project_code'  => 'project_code',
        'sub_code'      => 'sub_project',
        'activity_code' => 'activity_code',
    ];

    /**
     * Maps frontend scope identifiers to display-friendly labels.
     */
    private const SCOPE_LABEL_MAP = [
        'project_code'  => 'Project Code',
        'sub_code'      => 'Sub-Code',
        'activity_code' => 'Activity Code',
    ];

    /**
     * Sub-projects classified under the "Other 200" category.
     */
    private const OTHER_200_SUB_PROJECTS = [
        'Parental Leave',
        'Jury Duty',
        'Funeral',
        'Bereavement',
        'FMLA',
        'UTO',
    ];

    /**
     * All sub-projects that are explicitly categorized (PTO, Holiday, Other 200).
     * Used to exclude them from the "Other Non-Billable" catch-all.
     */
    private const CATEGORIZED_SUB_PROJECTS = [
        'PTO',
        'Holiday',
        'Parental Leave',
        'Jury Duty',
        'Funeral',
        'Bereavement',
        'FMLA',
        'UTO',
    ];

    /**
     * Project codes that should appear at the top of Historian results.
     */
    private const PRIORITY_PROJECT_CODES = [
        'CEG',
        'CEGEDU',
        'CEGMKTG',
        'CEGTRNG',
    ];

    // ================================================================
    //  INDEX
    // ================================================================

    /**
     * Display the timesheet page with initial data.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
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

            return view('time.employee-time', [
                'payPeriodData'   => $payPeriodsFormatted,
                'startDateCutOff' => $startDateCutOff,
                'endDateCutOff'   => $endDateCutOff,
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeTimeController error: ' . $e->getMessage());
            return response()->view('errors.500', ['error_message' => $e->getMessage()], 500);
        }
    }

    // ================================================================
    //  GET DATA
    // ================================================================

    /**
     * Fetch data for both the Historian and Aggregated display components.
     * Called via POST from the data-bridge.
     */
    public function getData(Request $request)
    {
        $componentKeys = ['historianTable', 'aggregatedTable'];

        // ----------------------------------------------------------
        //  STAGE 1: Global Validation & Parsing
        // ----------------------------------------------------------
        try {
            $parsed = $this->parseRequest($request);
        } catch (\Exception $e) {
            Log::error('EmployeeTime getData validation error: ' . $e->getMessage(), [
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
        //  STAGE 2: Shared Data Gathering (Two Pipelines)
        // ----------------------------------------------------------

        // --- Pipeline A: Historian ---
        $historianShared = null;
        try {
            $historianShared = $this->runHistorianPipeline(
                $parsed['userEmail'],
                $parsed['queryStart'],
                $parsed['queryEnd'],
                $parsed['granularity'],
                $parsed['scopes'],
                $parsed['periods'],
            );

        } catch (\Exception $e) {
            Log::error('EmployeeTime Historian pipeline error: ' . $e->getMessage(), [
                'inputs' => $request->all(),
                'trace'  => $e->getTraceAsString(),
            ]);
            $historianShared = ApiResponse::error('Failed to query historian data.');
        }

        // --- Pipeline B: Aggregated ---
        $aggregatedShared = null;
        try {
            $aggregatedShared = $this->runAggregatedPipeline(
                $parsed['userEmail'],
                $parsed['queryStart'],
                $parsed['queryEnd'],
                $parsed['granularity'],
                $parsed['periods'],
                $parsed['nonBillableCodes'],
                $parsed['wageType'],
            );
        } catch (\Exception $e) {
            Log::error('EmployeeTime Aggregated pipeline error: ' . $e->getMessage(), [
                'inputs' => $request->all(),
                'trace'  => $e->getTraceAsString(),
            ]);
            $aggregatedShared = ApiResponse::error('Failed to query aggregated data.');
        }

        // ----------------------------------------------------------
        //  STAGE 3: Individual Component Processing
        // ----------------------------------------------------------

        // --- Historian Table ---
        $historianResult = $this->buildHistorianComponent(
            $historianShared,
            $parsed['scopes'],
            $parsed['periods'],
            $parsed['periodHeaders'],
            $parsed['headerMode'],
        );
        Log::debug($historianResult);
        // --- Aggregated Table ---
        $aggregatedResult = $this->buildAggregatedComponent(
            $aggregatedShared,
            $parsed['periods'],
            $parsed['periodHeaders'],
            $parsed['granularity'],
        );

        return response()->json([
            'historianTable'  => $historianResult,
            'aggregatedTable' => $aggregatedResult,
            'granularity'     => $parsed['granularity']
        ], 200);
    }

    // ================================================================
    //  GET DATA HELPERS — Validation & Parsing
    // ================================================================

    /**
     * Parse and validate the incoming request. Returns all data needed
     * by later stages.
     *
     * @throws \Exception on validation failure
     */
    private function parseRequest(Request $request): array
    {
        $granularity = $request->input('granularity');
        $validGranularities = ['day', 'pay-period', 'month', 'quarter', 'year'];
        if (!in_array($granularity, $validGranularities, true)) {
            throw new \Exception("Invalid granularity: {$granularity}");
        }

        // --- Parse Scopes ---
        $rawScopes = $request->input('scopes', []);
        $scopes = [];
        foreach ($rawScopes as $scope) {
            if (!array_key_exists($scope, self::SCOPE_FIELD_MAP)) {
                throw new \Exception("Invalid scope: {$scope}");
            }
            $scopes[] = $scope;
        }
        if (empty($scopes)) {
            throw new \Exception('At least one scope must be selected.');
        }

        // --- Parse Date Range ---
        $startInput = $request->input('start');
        $endInput = $request->input('end');

        if ($granularity === 'day') {
            $queryStart = Carbon::parse($startInput)->startOfDay();
            $queryEnd = Carbon::parse($endInput)->endOfDay();
        } else {
            // Range-based granularities send {startDate, endDate} objects
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
        // Determine the Header Mode instead of a boolean
        $headerMode = 'standard';

        if ($granularity === 'pay-period') {
            // Long labels (e.g., "01-01-23 - 01-15-23") require more height
            $headerMode = 'compact-tall'; 
        } elseif ($granularity === 'day' && $queryStart->year !== $queryEnd->year) {
            // Short labels (e.g., "01-01-23") require less height, but still need rotation
            $headerMode = 'compact-short';
        }
        Log::debug($headerMode);

        // --- Resolve User ---
        //$userEmail = Auth::user()->email;
        $userEmail = "speichel@ceg-engineers.com";

        // --- Fetch Non-Billable Project Codes ---
        $nonBillableCodes = Project::where('is_internal', true)
            ->pluck('projectcode')
            ->toArray();
        
        Log::debug($nonBillableCodes);

        // --- Fetch User Wage Type (for overtime in aggregated table) ---
        //$wageType = Auth::user()->wage_type ?? 'salary'; TODO update
        $wageType = "salaried";

        return [
            'granularity'      => $granularity,
            'queryStart'       => $queryStart,
            'queryEnd'         => $queryEnd,
            'scopes'           => $scopes,
            'periods'          => $periods,
            'periodHeaders'    => $periodHeaders,
            'headerMode'       => $headerMode,
            'payPeriods'       => $payPeriods,
            'userEmail'        => $userEmail,
            'nonBillableCodes' => $nonBillableCodes,
            'wageType'         => $wageType,
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
        $seen = []; // Array to track unique periods

        foreach ($allPayPeriods as $year => $periods) {
            foreach ($periods as $period) {
                $ppStart = $period['start_date']; 
                $ppEnd = $period['end_date'];

                // Create a unique key for this specific period
                $uniqueKey = $ppStart->format('Y-m-d');

                // If we've already added this period, skip it
                if (isset($seen[$uniqueKey])) {
                    continue;
                }

                if ($ppEnd->gte($queryStart) && $ppStart->lte($queryEnd)) {
                    $relevant[] = [
                        'start' => $ppStart->copy(),
                        'end'   => $ppEnd->copy(),
                    ];
                    // Mark as seen
                    $seen[$uniqueKey] = true;
                }
            }
        }

        usort($relevant, fn($a, $b) => $a['start']->timestamp - $b['start']->timestamp);

        return $relevant;
    }

    // ================================================================
    //  GET DATA HELPERS — Period Generation & Headers
    // ================================================================

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
    //  GET DATA HELPERS — Pipeline Builders
    // ================================================================

    /**
     * Build and run the Historian aggregation pipeline.
     * Returns the raw pipeline result as an array.
     */
    private function runHistorianPipeline(
        string $userEmail,
        Carbon $queryStart,
        Carbon $queryEnd,
        string $granularity,
        array $scopes,
        array $periods,
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

        // --- Period Key Assignment ---
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

        // --- Group by Scopes + Period, sum hours ---
        $groupId = ['period' => '$period_key'];
        foreach ($scopes as $scope) {
            $field = self::SCOPE_FIELD_MAP[$scope];
            $groupId[$field] = '$' . $field;
        }

        $pipeline[] = [
            '$group' => [
                '_id'         => $groupId,
                'total_hours' => ['$sum' => '$hours'],
            ],
        ];

        // --- Group by Scopes only, collect periods as k/v pairs ---
        $scopeOnlyId = [];
        foreach ($scopes as $scope) {
            $field = self::SCOPE_FIELD_MAP[$scope];
            $scopeOnlyId[$field] = '$_id.' . $field;
        }

        $pipeline[] = [
            '$group' => [
                '_id'         => $scopeOnlyId,
                'periods'     => [
                    '$push' => [
                        'k' => '$_id.period',
                        'v' => '$total_hours',
                    ],
                ],
                'grand_total' => ['$sum' => '$total_hours'],
            ],
        ];

        // --- Pivot periods into columns via $replaceRoot ---
        $scopeProjection = [];
        foreach ($scopes as $scope) {
            $field = self::SCOPE_FIELD_MAP[$scope];
            $scopeProjection[$field] = '$_id.' . $field;
        }

        $pipeline[] = [
            '$replaceRoot' => [
                'newRoot' => [
                    '$mergeObjects' => [
                        $scopeProjection,
                        ['$arrayToObject' => '$periods'],
                        ['total' => '$grand_total'],
                    ],
                ],
            ],
        ];

        // --- Sort by scope fields ---
        $pipeline[] = [
            '$addFields' => [
                '_sort_priority' => [
                    '$cond' => [
                        'if'   => ['$in' => ['$project_code', self::PRIORITY_PROJECT_CODES]],
                        'then' => 0,
                        'else' => 1,
                    ],
                ],
            ],
        ];

        $sortSpec = ['_sort_priority' => 1];
        foreach ($scopes as $scope) {
            $field = self::SCOPE_FIELD_MAP[$scope];
            $sortSpec[$field] = 1;
        }
        $pipeline[] = ['$sort' => $sortSpec];

        // Remove temporary sort field from output
        $pipeline[] = [
            '$project' => ['_sort_priority' => 0],
        ];

        // --- Execute ---
        $collection = DB::connection('mongodb')->getCollection('hours');
        $cursor = $collection->aggregate($pipeline);

        return iterator_to_array($cursor);
    }

    /**
     * Build and run the Aggregated table aggregation pipeline.
     * Returns the raw pipeline result as an array.
     */
    private function runAggregatedPipeline(
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

        // --- Period Key Assignment ---
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

        // --- Group by period, compute category sums ---
        $groupStage = [
            '_id' => '$period_key',

            'pto' => [
                '$sum' => [
                    '$cond' => [
                        'if' => [
                            '$and' => [
                                ['$eq' => ['$project_code', 'CEG']],
                                ['$eq' => ['$sub_project', 'PTO']],
                            ],
                        ],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'holiday' => [
                '$sum' => [
                    '$cond' => [
                        'if' => [
                            '$and' => [
                                ['$eq' => ['$project_code', 'CEG']],
                                ['$eq' => ['$sub_project', 'Holiday']],
                            ],
                        ],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'other_200' => [
                '$sum' => [
                    '$cond' => [
                        'if' => [
                            '$and' => [
                                ['$eq' => ['$project_code', 'CEG']],
                                ['$in' => ['$sub_project', self::OTHER_200_SUB_PROJECTS]],
                            ],
                        ],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'other_nb' => [
                '$sum' => [
                    '$cond' => [
                        'if' => [
                            '$and' => [
                                ['$in' => ['$project_code', $nonBillableCodes]],
                                ['$or' => [
                                    ['$ne' => ['$project_code', 'CEG']],
                                    ['$and' => [
                                        ['$eq' => ['$project_code', 'CEG']],
                                        ['$not' => ['$in' => ['$sub_project', self::CATEGORIZED_SUB_PROJECTS]]],
                                    ]],
                                ]],
                            ],
                        ],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'billable' => [
                '$sum' => [
                    '$cond' => [
                        'if' => ['$not' => ['$in' => ['$project_code', $nonBillableCodes]]],
                        'then' => '$hours',
                        'else' => 0,
                    ],
                ],
            ],

            'total_hours' => ['$sum' => '$hours'],
        ];

        $pipeline[] = ['$group' => $groupStage];

        // --- Project computed fields ---
        $projectStage = [
            '_id'        => 0,
            'period_key' => '$_id',
            'pto'        => 1,
            'holiday'    => 1,
            'other_200'  => 1,
            'other_nb'   => 1,
            'total_nb'   => ['$add' => ['$pto', '$holiday', '$other_200', '$other_nb']],
            'billable'   => 1,
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
                // Non-hourly employees never have overtime
                $projectStage['overtime'] = ['$literal' => 0];
            }
        }

        $pipeline[] = ['$project' => $projectStage];

        // --- Sort chronologically ---
        $pipeline[] = ['$sort' => ['period_key' => 1]];

        // --- Execute ---
        $collection = DB::connection('mongodb')->getCollection('hours');
        $cursor = $collection->aggregate($pipeline);

        return iterator_to_array($cursor);
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
     * pay-period granularity. Injects the pay period definitions as an
     * inline array and uses $filter + $arrayElemAt to match each document.
     *
     * Returns two stages: $addFields (to tag the pay period) and $match
     * (to drop unmatched documents).
     */
    private function getPayPeriodKeyStages(array $periods): array
    {
        // Build the inline pay period array for the pipeline
        $ppArray = [];
        foreach ($periods as $p) {
            $ppArray[] = [
                'start_date' => new \MongoDB\BSON\UTCDateTime($p['start']->getTimestamp() * 1000),
                'end_date'   => new \MongoDB\BSON\UTCDateTime($p['end']->getTimestamp() * 1000),
                'key'        => $p['key'],
            ];
        }

        return [
            // Tag each document with its matching pay period
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
            // Drop documents that don't fall in any pay period
            [
                '$match' => ['_matched_pp' => ['$ne' => null]],
            ],
            // Extract the period_key and clean up the temporary field
            [
                '$addFields' => [
                    'period_key'  => '$_matched_pp.key',
                ],
            ],
            [
                '$project' => ['_matched_pp' => 0],
            ],
        ];
    }

    // ================================================================
    //  GET DATA HELPERS — Component Result Processing
    // ================================================================

    /**
     * Process raw Historian pipeline results into the final ApiResponse.
     * Fills in zeros for any missing period columns.
     */
    private function buildHistorianComponent(
        $historianShared,
        array $scopes,
        array $periods,
        array $periodHeaders,
        string $headerMode,
    ): object {
        // If the shared data already carries an error, propagate it
        if ($historianShared instanceof \App\Http\Responses\ApiResponse) {
            return $historianShared;
        }

        try {
            $periodKeys = array_map(fn($p) => $p['key'], $periods);

            // Build scope column metadata
            $scopeColumns = [];
            foreach ($scopes as $scope) {
                $scopeColumns[] = [
                    'key'   => self::SCOPE_FIELD_MAP[$scope],
                    'label' => self::SCOPE_LABEL_MAP[$scope],
                ];
            }

            // Build period column metadata (ordered)
            $periodColumns = [];
            foreach ($periods as $p) {
                $periodColumns[] = [
                    'key'   => $p['key'],
                    'label' => $periodHeaders[$p['key']],
                ];
            }

            // Process rows — ensure every period key exists
            $rows = [];
            foreach ($historianShared as $doc) {
                $row = [];

                // Convert BSONDocument to array if needed
                $docArray = ($doc instanceof \MongoDB\Model\BSONDocument)
                    ? (array) $doc
                    : (array) $doc;

                // Scope fields
                foreach ($scopes as $scope) {
                    $field = self::SCOPE_FIELD_MAP[$scope];
                    $row[$field] = $docArray[$field] ?? '';
                }

                // Period fields — fill missing with 0
                foreach ($periodKeys as $key) {
                    $row[$key] = isset($docArray[$key])
                        ? round((float) $docArray[$key], 2)
                        : 0;
                }

                // Total
                $row['total'] = isset($docArray['total'])
                    ? round((float) $docArray['total'], 2)
                    : 0;

                $rows[] = $row;
            }

            return ApiResponse::success([
                'scopeColumns'   => $scopeColumns,
                'periodColumns'  => $periodColumns,
                'rows'           => $rows,
                'headerMode'     => $headerMode,
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeTime Historian processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Unable to process historian data.');
        }
    }

    /**
     * Process raw Aggregated pipeline results into the final ApiResponse.
     * Fills in zero-rows for any periods with no data.
     */
    private function buildAggregatedComponent(
        $aggregatedShared,
        array $periods,
        array $periodHeaders,
        string $granularity,
    ): object {
        // If the shared data already carries an error, propagate it
        if ($aggregatedShared instanceof \App\Http\Responses\ApiResponse) {
            return $aggregatedShared;
        }

        try {
            $showOvertime = $granularity === 'pay-period';

            // Index raw results by period_key for fast lookup
            $resultsByKey = [];
            foreach ($aggregatedShared as $doc) {
                $docArray = ($doc instanceof \MongoDB\Model\BSONDocument)
                    ? (array) $doc
                    : (array) $doc;

                $key = $docArray['period_key'] ?? null;
                if ($key !== null) {
                    $resultsByKey[(string) $key] = $docArray;
                }
            }

            // Build rows — one per period, filling missing with zeros
            $rows = [];
            $zeroRow = [
                'pto'                  => 0,
                'holiday'              => 0,
                'other_200'            => 0,
                'other_nb'             => 0,
                'total_nb'             => 0,
                'billable'             => 0,
                'total_hours'          => 0,
                'billable_percentage'  => 0,
            ];
            if ($showOvertime) {
                $zeroRow['overtime'] = 0;
            }

            foreach ($periods as $p) {
                $key = $p['key'];

                if (isset($resultsByKey[$key])) {
                    $doc = $resultsByKey[$key];
                    $row = [
                        'period_key'           => $key,
                        'period_label'         => $periodHeaders[$key],
                        'pto'                  => round((float) ($doc['pto'] ?? 0), 2),
                        'holiday'              => round((float) ($doc['holiday'] ?? 0), 2),
                        'other_200'            => round((float) ($doc['other_200'] ?? 0), 2),
                        'other_nb'             => round((float) ($doc['other_nb'] ?? 0), 2),
                        'total_nb'             => round((float) ($doc['total_nb'] ?? 0), 2),
                        'billable'             => round((float) ($doc['billable'] ?? 0), 2),
                        'total_hours'          => round((float) ($doc['total_hours'] ?? 0), 2),
                        'billable_percentage'  => round((float) ($doc['billable_percentage'] ?? 0), 1),
                    ];
                    if ($showOvertime) {
                        $row['overtime'] = round((float) ($doc['overtime'] ?? 0), 2);
                    }
                } else {
                    $row = array_merge(
                        ['period_key' => $key, 'period_label' => $periodHeaders[$key]],
                        $zeroRow,
                    );
                }

                $rows[] = $row;
            }

            // Build column definitions
            $columns = [
                ['key' => 'period_label', 'label' => 'Period'],
                ['key' => 'pto',                 'label' => 'PTO'],
                ['key' => 'holiday',             'label' => 'Holiday'],
                ['key' => 'other_200',           'label' => 'Other T.O.'],
                ['key' => 'other_nb',            'label' => 'Other N.B.'],
                ['key' => 'total_nb',            'label' => 'Total N.B.'],
                ['key' => 'billable',            'label' => 'Billable'],
                ['key' => 'total_hours',         'label' => 'Total'],
                ['key' => 'billable_percentage', 'label' => 'Billable %'],
            ];
            if ($showOvertime) {
                $columns[] = ['key' => 'overtime', 'label' => 'Overtime'];
            }

            return ApiResponse::success([
                'columns'      => $columns,
                'rows'         => $rows,
                'showOvertime' => $showOvertime,
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeTime Aggregated processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Unable to process aggregated data.');
        }
    }
}