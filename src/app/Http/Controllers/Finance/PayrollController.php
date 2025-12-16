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
        'activeFilters' => 'present|array'
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

        // Handle 'active' filter
        if (in_array('active', $activeFilters)) {
            $userMatchFilter['active'] = true;
        }

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
            // This replaces the hardcoded { active: true } from the raw pipeline.
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
            // Note: Field references are slightly different because we started on the User object.
            // (e.g., '$employee_number' is now at the root, not inside 'userInfo')
            [
                '$group' => [
                    '_id' => [
                        'mongo_id' => '$_id', 
                        'employee_id' => '$employee_number',
                        'name' => '$name',
                    ],
                    'expected_billable' => ['$first' => '$expected_billable'],
                    
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
        $results = User::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        })->toArray();

        Log::debug($results);

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
            'totalHours' => $totalCompanyHours,
            'totalOvertime' => $totalCompanyOvertime,
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
