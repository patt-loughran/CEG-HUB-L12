<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

use App\Models\Hour;
use App\Models\GlobalDoc;
use App\Models\Project;
use App\Models\User;
use Log;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        try {
            $dateRanges = $this->getDateRanges();
            return view('finance.payroll', compact('dateRanges'));
        }
        catch (\Exception $e) {
            Log::error('PayrollController error in index(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->view('errors.500', ['error_message' => $e->getMessage()], 500);
        }
        
    }

    private function getDateRanges()
    {
        $payPeriodsDoc = GlobalDoc::where('name', 'Pay-Periods')->first();
        
        if (!$payPeriodsDoc) {
            throw new \Exception('Pay-Period document not found in MongoDB');
        }
        
        $payPeriodsData = $payPeriodsDoc->{'Pay-Periods'};
        $result = [];

        $currentDate = Carbon::today();
        $currentYear = (int)date('Y');
        
        // Process each year
        foreach ($payPeriodsData as $year => $periods) {
            if ((int)$year > $currentYear) continue;

            $payPeriods = [];
            
            // Process each pay period in the year
            foreach ($periods as $index => $period) {
                // The dates are already Carbon objects, so use them directly
                $startDate = $period['start_date'];
                $endDate = $period['end_date'];
                
                // If for some reason they're not Carbon objects, convert them
                if (!($startDate instanceof \Carbon\Carbon)) {
                    $startDate = \Carbon\Carbon::parse($startDate);
                }
                if (!($endDate instanceof \Carbon\Carbon)) {
                    $endDate = \Carbon\Carbon::parse($endDate);
                }

                if ((int)$year === $currentYear && $startDate->greaterThan($currentDate)) {
                    continue;
                }

                // Format pay period number (PP 01, PP 02, etc.)
                $ppNumber = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                
                // Format dates as MM/DD
                $startFormatted = $startDate->format('m/d');
                $endFormatted = $endDate->format('m/d');
                
                // Create pay period string
                $payPeriods[] = "PP {$ppNumber} ({$startFormatted} - {$endFormatted})";
            }

            usort($payPeriods, function ($a, $b) {
                // Extract PP numbers from strings
                preg_match('/PP (\d+)/', $a, $matchA);
                preg_match('/PP (\d+)/', $b, $matchB);
                return (int)$matchB[1] <=> (int)$matchA[1]; // Descending
            });
            
            $result[$year] = $payPeriods;
        }
    
        return $result;
    }

    public function getData(Request $request) {
        // Stage 1: Initial Validation
        try{
            $validatedData = $request->validate([
                'year' => 'required|string',
                'dateRangeDropdown' => 'required|string',
                'activeFilters' => 'present|array'
            ]);

            $year = $validatedData['year'];
            $startDate = null;
            $endDate = null;
            $activeFilters = $validatedData['activeFilters'];

            // Use regex to extract the MM/DD parts from a string like "PP 05 (03/16 - 03/29)"
            preg_match('/\((\d{2}\/\d{2}) - (\d{2}\/\d{2})\)/', $validatedData['dateRangeDropdown'], $matches);

            if (count($matches) === 3) {
                $startString = $matches[1] . '/' . $year; // e.g., "03/16/2024"
                $endString = $matches[2] . '/' . $year;   // e.g., "03/29/2024"

                $startDate = Carbon::createFromFormat('m/d/Y', $startString)->startOfDay();
                $endDate = Carbon::createFromFormat('m/d/Y', $endString)->endOfDay();

                // NUANCE: Handle pay periods that cross over into the next year.
                // If the end date is earlier in the year than the start date, it must be in the following year.
                if ($endDate->lt($startDate)) {
                    $endDate->addYear();
                }
            }

            // Failsafe: if date parsing failed for any reason, return an error.
            if (!$startDate || !$endDate) {
                throw new \Exception('Failed to parse start and end date');
            }
        }
        catch (\Exception $e) {
            Log::error('Date parsing failed in Payroll getData() Stage 1:' . $e->getMessage(), [
                    'requestData' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);

            $data = [
                'tableData'                 => ApiResponse::error("Validation Failed" . $e->getMessage()),
                'totalHours'                => ApiResponse::error("Validation Failed" . $e->getMessage()),
                'totalOvertime'             => ApiResponse::error("Validation Failed" . $e->getMessage()),
                'averageBillablePercentage' => ApiResponse::error("Validation Failed" . $e->getMessage()),
            ];

            return response()->json($data, 200);
        }

        // Stage 2: Shared Data Gathering
        try {
            // Gather data needed for aggregation pipeline
            $internalProjectCodes = Project::where('is_internal', true)->pluck('projectcode')->toArray();
            $codes_200 = GlobalDoc::where('name', "200_codes")->first()?->{'200_codes'} ?? ['Parental Leave', 'Jury Duty', 'Funeral', 'Bereavement', 'FMLA', 'UTO'];
            $allSpecialCEGSubProjects = array_unique(array_merge(['PTO', 'Holiday'], $codes_200));

            $userMatchFilter = [];

            $userMatchFilter['employment_history'] = [
                '$elemMatch' => [
                    'start_date' => ['$lte' => new \MongoDB\BSON\UTCDateTime($endDate->getTimestamp() * 1000)],
                    '$or' => [
                        ['end_date' => ['$gte' => new \MongoDB\BSON\UTCDateTime($startDate->getTimestamp() * 1000)]],
                        ['end_date' => null]
                    ]
                ]
            ];

            // Handle wage types (Hourly AND/OR Salaried)
            $selectedWageTypes = [];
            if (in_array('hourly', $activeFilters)) $selectedWageTypes[] = 'hourly';
            if (in_array('salaried', $activeFilters)) $selectedWageTypes[] = 'salaried';

            // If wage types are selected, use the $in operator to match ANY of them
            if (!empty($selectedWageTypes)) {
                $userMatchFilter['wage_type'] = ['$in' => $selectedWageTypes];
            }

            $pipeline = [
                // Stage 1: Filter Users FIRST.
                // This ensures we don't waste time looking up hours for inactive users.
                (!empty($userMatchFilter)) ? ['$match' => $userMatchFilter] : null,

                // Stage 2: The "Left Join" Lookup.
                // We look into the 'hours' collection, filtering by date immediately inside the join.
                [
                    '$lookup' => [
                        'from' => 'hours',
                        'let' => ['user_email' => '$email'], // Pass the user's email to the sub-pipeline
                        'pipeline' => [
                            [
                                '$match' => [
                                    '$expr' => [
                                        '$eq' => ['$user_email', '$$user_email'] // Match email
                                    ],
                                    // Apply Date Filters here inside the lookup
                                    'date' => [
                                        '$gte' => new \MongoDB\BSON\UTCDateTime($startDate->getTimestamp() * 1000),
                                        '$lte' => new \MongoDB\BSON\UTCDateTime($endDate->getTimestamp() * 1000),
                                    ],
                                ],
                            ],
                        ],
                        'as' => 'hours_entries',
                    ],
                ],

                // Stage 3: Unwind the hours, BUT keep users with empty arrays.
                // This is what allows "Zero Hour" employees to show up in the report.
                [
                    '$unwind' => [
                        'path' => '$hours_entries',
                        'preserveNullAndEmptyArrays' => true,
                    ],
                ],

                // Stage 4: Grouping & Calculation
                [
                    '$group' => [
                        '_id' => [
                            'mongo_id' => '$_id', 
                            'employee_id' => '$employee_number',
                            'name' => '$name',
                        ],
                        'expected_billable' => ['$first' => '$expected_billable'],
                        'wage_type' => ['$first' => '$wage_type'],
                        
                        // PTO Calculation
                        'pto' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => [
                                        '$and' => [
                                            ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                            ['$eq' => ['$hours_entries.sub_project', 'PTO']],
                                        ],
                                    ],
                                    'then' => '$hours_entries.hours',
                                    'else' => 0,
                                ],
                            ],
                        ],

                        // Holiday Calculation
                        'holiday' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => [
                                        '$and' => [
                                            ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                            ['$eq' => ['$hours_entries.sub_project', 'Holiday']],
                                        ],
                                    ],
                                    'then' => '$hours_entries.hours',
                                    'else' => 0,
                                ],
                            ],
                        ],

                        // Other 200 Calculation (Using your Laravel variable $codes_200)
                        'other_200' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => [
                                        '$and' => [
                                            ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                            ['$in' => ['$hours_entries.sub_project', $codes_200]],
                                            ['$ne' => ['$hours_entries.sub_project', 'PTO']],
                                            ['$ne' => ['$hours_entries.sub_project', 'Holiday']],
                                        ],
                                    ],
                                    'then' => '$hours_entries.hours',
                                    'else' => 0,
                                ],
                            ],
                        ],

                        // Other Non-Billable Calculation (Using your Laravel variables)
                        'other_nb' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => [
                                        '$and' => [
                                            ['$in' => ['$hours_entries.project_code', $internalProjectCodes]],
                                            ['$or' => [
                                                ['$ne' => ['$hours_entries.project_code', 'CEG']],
                                                ['$and' => [
                                                    ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                                    ['$not' => ['$in' => ['$hours_entries.sub_project', $allSpecialCEGSubProjects]]]
                                                ]]
                                            ]]
                                        ]
                                    ],
                                    'then' => '$hours_entries.hours',
                                    'else' => 0,
                                ],
                            ],
                        ],

                        // Billable Calculation (Using your Laravel variables)
                        'billable' => [
                            '$sum' => [
                                '$cond' => [
                                    'if' => [
                                        '$and' => [
                                            ['$ne' => ['$hours_entries.project_code', 'CEG']],
                                            ['$not' => ['$in' => ['$hours_entries.project_code', $internalProjectCodes]]],
                                        ],
                                    ],
                                    'then' => '$hours_entries.hours',
                                    'else' => 0,
                                        ],
                                    ],
                                ],
                        
                        // Total Hours
                        'total_hours' => ['$sum' => '$hours_entries.hours'],
                    ],
                ],

                // Stage 5: Projection (Math) - Unchanged
                [
                    '$project' => [
                        '_id' => 0,
                        'id' => ['$toString' => '$_id.mongo_id'],
                        'employee_name' => '$_id.name',
                        'employee_id' => '$_id.employee_id',
                        'expected_billable' => '$expected_billable',
                        'wage_type' => '$wage_type',
                        'pto' => '$pto',
                        'holiday' => '$holiday',
                        'other_200' => '$other_200',
                        'other_nb' => '$other_nb',
                        'total_nb' => [
                            '$add' => ['$pto', '$holiday', '$other_200', '$other_nb'],
                        ],
                        'billable' => '$billable',
                        'total_hours' => '$total_hours',
                        'billable_percentage' => [
                            '$cond' => [
                                'if' => ['$gt' => ['$total_hours', 0]],
                                'then' => [
                                    '$multiply' => [['$divide' => ['$billable', '$total_hours']], 100],
                                ],
                                'else' => 0,
                            ],
                        ],
                        'overtime' => [
                            '$cond' => [
                                'if' => [
                                    '$and' => [
                                        ['$eq' => ['$wage_type', 'hourly']],
                                        ['$gt' => ['$total_hours', 80]]
                                    ]
                                ],
                                'then' => ['$subtract' => ['$total_hours', 80]],
                                'else' => 0,
                            ],
                        ],
                        'grouped' => ['$literal' => false]
                    ],
                ],

                // Stage 6: Sort - Unchanged
                [
                    '$sort' => [
                        'employee_name' => 1
                    ]
                ]
            ];

            // Clean up the pipeline array (remove nulls if Stage 1 conditional failed, though I added a default)
            $pipeline = array_values(array_filter($pipeline));

            // EXECUTION: IMPORTANT - Run this on the User model
            $aggregatedHours = User::raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline);
            })->toArray();
        }
        catch (\Exception $e) {
            Log::error('Error in Shared Data Gathering' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);

            $aggregatedHours = ApiResponse::error($e->getMessage());
        }

        // Stage 3: Individual Components

        // Table Data
        try {
            if ($aggregatedHours instanceof ApiResponse) {
                $tableDataResponse = $aggregatedHours;
            }
            else {
                // we already have the main tableData from $aggregatedHours, so now we just calculate summaryRows data
                $groupedByType = [];
            
                foreach ($aggregatedHours as $item) {
                    // Default to 'Other' if wage_type is missing, though your pipeline ensures it exists
                    $type = $item['wage_type'] ?? 'Other';
                    
                    if (!isset($groupedByType[$type])) {
                        $groupedByType[$type] = [
                            'pto' => 0,
                            'holiday' => 0,
                            'other_200' => 0,
                            'other_nb' => 0,
                            'total_nb' => 0,
                            'billable' => 0,
                            'total_hours' => 0,
                            'overtime' => 0,
                        ];
                    }

                    $groupedByType[$type]['pto'] += $item['pto'];
                    $groupedByType[$type]['holiday'] += $item['holiday'];
                    $groupedByType[$type]['other_200'] += $item['other_200'];
                    $groupedByType[$type]['other_nb'] += $item['other_nb'];
                    $groupedByType[$type]['total_nb'] += $item['total_nb'];
                    $groupedByType[$type]['billable'] += $item['billable'];
                    $groupedByType[$type]['total_hours'] += $item['total_hours'];
                    $groupedByType[$type]['overtime'] += $item['overtime'];
                }

                $summaryRows = [];

                // 2. Build the final summary row structure
                foreach ($groupedByType as $type => $totals) {
                    // Calculate weighted billable percentage (Sum of Billable / Sum of Total Hours)
                    $weightedBillablePct = ($totals['total_hours'] > 0) 
                        ? ($totals['billable'] / $totals['total_hours']) * 100 
                        : 0;

                    $summaryRows[] = [
                        'id' => 'summary-' . $type,
                        'employee_name' => ucfirst($type) . ' Totals', // e.g., "Hourly Totals"
                        'employee_id' => 'N/A',
                        'expected_billable' => null,
                        'wage_type' => $type,
                        'pto' => $totals['pto'],
                        'holiday' => $totals['holiday'],
                        'other_200' => $totals['other_200'],
                        'other_nb' => $totals['other_nb'],
                        'total_nb' => $totals['total_nb'],
                        'billable' => $totals['billable'],
                        'total_hours' => $totals['total_hours'],
                        'billable_percentage' => round($weightedBillablePct, 2),
                        'overtime' => $totals['overtime'],
                        'grouped' => true // Distinct flag for frontend styling
                    ];
                }

                $tableData = ["tableData" => $aggregatedHours, "summaryRows" => $summaryRows, "payPeriodIdentifier" => $validatedData['dateRangeDropdown']];
                $tableDataResponse = ApiResponse::success($tableData);
            }
        }
        catch (\Exception $e) {
            Log::error('Error in calculating Summary Rows for tableData' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);
            $tableDataResponse = ApiResponse::error("Error in calculating summary rows " . $e->getMessage());
        }
        
        // simple stat tiles
        try {
            if ($aggregatedHours instanceof ApiResponse) {
                $totalCompanyHoursResponse = $aggregatedHours;
                $totalCompanyOvertimeResponse = $aggregatedHours;
                $averageBillablePercentageResponse = $aggregatedHours;
            }
            else {
                $totalCompanyHours = 0;
                $totalCompanyOvertime = 0;
                $totalBillableHoursOfBillableStaff = 0; // For weighted average
                $totalHoursOfBillableStaff = 0;         // For weighted average

                foreach ($aggregatedHours as $row) {
                    // Sum company-wide totals regardless of billable status
                    $totalCompanyHours += $row['total_hours'];
                    $totalCompanyOvertime += $row['overtime'];

                    // Only include billable staff in the average calculation
                    if (isset($row['expected_billable']) && $row['expected_billable']) {
                        $totalBillableHoursOfBillableStaff += $row['billable'];
                        $totalHoursOfBillableStaff += $row['total_hours'];
                    }
                }

                // Calculate the weighted average billable percentage
                $averageBillablePercentage = ($totalHoursOfBillableStaff > 0)
                    ? ($totalBillableHoursOfBillableStaff / $totalHoursOfBillableStaff) * 100
                    : 0;
                $averageBillablePercentage = round($averageBillablePercentage, 2);

                $totalCompanyHoursResponse = ApiResponse::success($totalCompanyHours);
                $totalCompanyOvertimeResponse = ApiResponse::success($totalCompanyOvertime);
                $averageBillablePercentageResponse = ApiResponse::success($averageBillablePercentage);
            }
        }
        catch (\Exception $e) {
            Log::error('Error in calculating stat tiles' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all()
                ]);
            
            $totalCompanyHoursResponse = ApiResponse::error('Error in calculating stat tiles' . $e->getMessage());
            $totalCompanyOvertimeResponse = ApiResponse::error('Error in calculating stat tiles' . $e->getMessage());
            $averageBillablePercentageResponse = ApiResponse::error('Error in calculating stat tiles' . $e->getMessage());
        }
        

        // Prepare the response
        $response = [
            'tableData' => $tableDataResponse,
            'totalHours' => $totalCompanyHoursResponse,
            'totalOvertime' => $totalCompanyOvertimeResponse,
            'averageBillablePercentage' => $averageBillablePercentageResponse
        ];

        return response()->json($response, 200);
    }
}