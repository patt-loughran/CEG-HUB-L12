<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        $dateRanges = $this->getDateRanges();
        return view('finance.payroll', compact('dateRanges'));
    }

    private function getDateRanges()
    {
        // Fetch the Pay-Periods document from MongoDB
        $payPeriodsDoc = GlobalDoc::where('name', 'Pay-Periods')->first();
        
        if (!$payPeriodsDoc) {
            return [];
        }
        
        $payPeriodsData = $payPeriodsDoc->{'Pay-Periods'};
        $result = [];

        $currentDate = Carbon::today();
        $currentYear = (int)date('Y');
        
        // Process each year
        foreach ($payPeriodsData as $year => $periods) {
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
        try{

        $validated = $request->validate([
        'year' => 'required|string',
        'dateRangeDropdown' => 'required|string',
        'activeFilters' => 'required|array'
        ]);

        $year = $validated['year'];
        $startDate = null;
        $endDate = null;
        $activeFilters = $validated['activeFilters'];

        // Use regex to extract the MM/DD parts from a string like "PP 05 (03/16 - 03/29)"
        preg_match('/\((\d{2}\/\d{2}) - (\d{2}\/\d{2})\)/', $validated['dateRangeDropdown'], $matches);

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
            return response()->json(['error' => 'Could not determine a valid date range.'], 400);
        }

        // 1. Fetch internal project codes and 200 codes
        $internalProjectCodes = Project::where('is_internal', true)->pluck('projectcode')->toArray();
        $codes_200 = GlobalDoc::where('name', "200_codes")->first()?->{'200_codes'} ?? ['Parental Leave', 'Jury Duty', 'Funeral', 'Bereavement', 'FMLA', 'UTO'];
        $allSpecialCEGSubProjects = array_unique(array_merge(['PTO', 'Holiday'], $codes_200));

        $userMatchFilter = [];
        if (in_array('active', $activeFilters)) {
            // Correct Path: Point to 'userInfo.active'
            $userMatchFilter['userInfo.active'] = true;
        }

        if (in_array('hourly', $activeFilters)) {
            // Correct Path: Point to 'userInfo.wage_type'
            $userMatchFilter['userInfo.wage_type'] = 'hourly';
        }
        if (in_array('salaried', $activeFilters)) {
            // Correct Path: Point to 'userInfo.wage_type'
            $userMatchFilter['userInfo.wage_type'] = 'salaried';
        }

        $pipeline = [
            // Stage 1: Match hours within the date range (Unchanged)
            [
                '$match' => [
                    'date' => [
                        '$gte' => new \MongoDB\BSON\UTCDateTime($startDate->getTimestamp() * 1000),
                        '$lte' => new \MongoDB\BSON\UTCDateTime($endDate->getTimestamp() * 1000),
                    ],
                ],
            ],

            // Stage 2: [NEW] Pre-group by user email to reduce lookup operations
            [
                '$group' => [
                    '_id' => '$user_email',
                    // Store all related hour documents in an array for later processing
                    'hours_entries' => ['$push' => '$$ROOT'],
                ],
            ],

            // Stage 3: [NEW] Perform the lookup on the much smaller, grouped dataset
            [
                '$lookup' => [
                    'from' => 'users',
                    'localField' => '_id', // Join on the grouped user_email
                    'foreignField' => 'email',
                    'as' => 'userInfo', // Use a new name to avoid conflicts
                ],
            ],

            // Stage 4: [NEW] Deconstruct the userInfo array
            [
                '$unwind' => '$userInfo',
            ],

            // Stage 5: [MOVED & MODIFIED] Apply user filters *after* the lookup
            // IMPORTANT: Your filter logic must now reference the 'userInfo' object.
            // e.g., 'user.active' becomes 'userInfo.active'
            ($userMatchFilter) ? ['$match' => $userMatchFilter] : null,

            // Stage 6: [NEW] Deconstruct the hours_entries array to process each entry
            [
                '$unwind' => '$hours_entries',
            ],

            // Stage 7: [MODIFIED] Final grouping for calculations. All field paths are updated.
            [
                '$group' => [
                    '_id' => [
                        'employee_id' => '$userInfo.employee_number', // Path updated
                        'name' => '$userInfo.name',                   // Path updated
                    ],
                    'expected_billable' => ['$first' => '$userInfo.expected_billable'], // Path updated
                    'pto' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => [
                                    '$and' => [
                                        // Paths updated to look inside 'hours_entries'
                                        ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                        ['$eq' => ['$hours_entries.sub_project', 'PTO']],
                                    ],
                                ],
                                'then' => '$hours_entries.hours', // Path updated
                                'else' => 0,
                            ],
                        ],
                    ],
                    'holiday' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => [
                                    '$and' => [
                                        ['$eq' => ['$hours_entries.project_code', 'CEG']],
                                        ['$eq' => ['$hours_entries.sub_project', 'Holiday']],
                                    ],
                                ],
                                'then' => '$hours_entries.hours', // Path updated
                                'else' => 0,
                            ],
                        ],
                    ],
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
                                'then' => '$hours_entries.hours', // Path updated
                                'else' => 0,
                            ],
                        ],
                    ],
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
                                'then' => '$hours_entries.hours', // Path updated
                                'else' => 0,
                            ],
                        ],
                    ],
                    'billable' => [
                        '$sum' => [
                            '$cond' => [
                                'if' => [
                                    '$and' => [
                                        ['$ne' => ['$hours_entries.project_code', 'CEG']],
                                        ['$not' => ['$in' => ['$hours_entries.project_code', $internalProjectCodes]]],
                                    ],
                                ],
                                'then' => '$hours_entries.hours', // Path updated
                                'else' => 0,
                                    ],
                                ],
                            ],
                    'total_hours' => ['$sum' => '$hours_entries.hours'], // Path updated
                ],
            ],

            // Stage 8: Project the final structure (Unchanged)
            [
                '$project' => [
                    '_id' => 0,
                    'employee_name' => '$_id.name',
                    'employee_id' => '$_id.employee_id',
                    'expected_billable' => '$expected_billable',
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
                            'if' => ['$gt' => ['$total_hours', 80]],
                            'then' => ['$subtract' => ['$total_hours', 80]],
                            'else' => 0,
                        ],
                    ],
                ],
            ],

            // Stage 9: Sort the results by employee name (Unchanged)
            [
                '$sort' => [
                    'employee_name' => 1
                ]
            ]
        ];
                
        // Remove null stage if no user filters are active
        $pipeline = array_values(array_filter($pipeline));

        $results = Hour::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        })->toArray();

       // Initialize totals
        $totalCompanyHours = 0;
        $totalCompanyOvertime = 0;
        $totalBillableHoursOfBillableStaff = 0; // For weighted average
        $totalHoursOfBillableStaff = 0;         // For weighted average

        foreach ($results as $row) {
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

        // Prepare the response
        $response = [
            'tableData' => $results,
            'totalHours' => $totalCompanyHours, // Use the more descriptive variable name
            'totalOvertime' => $totalCompanyOvertime, // Use the more descriptive variable name
            'averageBillablePercentage' => round($averageBillablePercentage, 2),
            'payPeriodIdentifier' => $validated['dateRangeDropdown']
        ];
        return response()->json($response);

        } catch (\Exception $e) {
        Log::error('PayrollController getData error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
    }

}
