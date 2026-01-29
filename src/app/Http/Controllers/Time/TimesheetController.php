<?php

namespace App\Http\Controllers\Time;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ApiResult;
use App\Models\Hour;
use App\Models\GlobalDoc;
use App\Models\User;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\PinnedProject;
use Carbon\Carbon;

class TimesheetController extends Controller
{
    private $numPayPeriods = 13; // current pay-period +/- 6 pay-periods. !NOTE! Current implementation of this controller assumes equal number of pay-periods before and after. If this changes, you will need to change code.
    /**
     * Display the timesheet page with initial data.
     *
     * @return \Illuminate\View\View
     */
    public function index() {
        try {
            $user = Auth::user(); // used eventually
            $user = User::where('email', 'ploughran@ceg-engineers.com')->first();
            $today = Carbon::today();

            // Fetch pay periods from the database
            $payPeriodsDoc = GlobalDoc::where('name', 'Pay-Periods')->firstOrFail();

            $surroundingPayPeriods = $this->getSurroundingPayPeriods($payPeriodsDoc, $today);

            // Prepare data for the date navigator
            $dateNavigatorData = $this->formatPayPeriodsForNavigator($surroundingPayPeriods);
            $sequenceNum = 1;

            return view('time.timesheet', ['dateNavigatorData'  => $dateNavigatorData, 'sequenceNum' => $sequenceNum]);

        } catch (\Exception $e) {
            Log::error('TimesheetController error in index(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->view('errors.500', ['error_message' => $e->getMessage()], 500);
        }
    }

    // ===================================================================
    // INDEX HELPER FUNCTIONS
    // ===================================================================

    /**
     * Retrieves the current pay period, the 6 preceding, and the 6 succeeding pay periods.
     *
     * This function handles edge cases at the beginning and end of a calendar year,
     * where a single pay period may span across two years.
     *
     * @param array $payPeriodsDoc The mongo doc as an array, containing pay periods, structured by year.
     * @param Carbon $today The Carbon instance for the current date.
     * @return array An array of the 13 relevant pay periods, or an empty array if the current period cannot be found.
     */
    private function getSurroundingPayPeriods($payPeriodsDoc, Carbon $today) {
        // Define the range of years to check to handle year-end and year-start crossovers.
        $prevYear = (string) $today->copy()->subYear()->year;
        $currentYear = (string) $today->year;
        $nextYear = (string) $today->copy()->addYear()->year;

        // Retrieve pay periods for the three-year span. Using the null coalescing operator
        // ensures we have an empty array for years that might not be in the data source.
        $periodsPrevYear = $payPeriodsDoc['Pay-Periods'][$prevYear] ?? [];
        $periodsCurrentYear = $payPeriodsDoc['Pay-Periods'][$currentYear] ?? [];
        $periodsNextYear = $payPeriodsDoc['Pay-Periods'][$nextYear] ?? [];

        // Combine all periods into a single, sequential array. This allows us to find the
        // current period regardless of which year's data it resides in.
        $surroundingPeriods = array_merge($periodsPrevYear, $periodsCurrentYear, $periodsNextYear);

        if (empty($surroundingPeriods)) {
            throw new \Exception("no relevant pay-periods found");
        }

        $currentIndex = -1;

        // Iterate through the combined list to find which pay period contains the '$today' date.
        foreach ($surroundingPeriods as $index => $period) {
            $startDate = $period['start_date'];
            $endDate = $period['end_date'];

            if ($today->between($startDate, $endDate)) {
                $currentIndex = $index;
                break;
            }
        }

        // If '$today' doesn't fall within any pay period in the three-year span
        if ($currentIndex === -1) {
            throw new \Exception('todays date not found in available pay-periods');
        }

        // Calculate the starting index for our slice. We want 6 periods before the current one.
        // max(0, ...) ensures the index is never negative.
        $startIndex = max(0, $currentIndex - 6);

        // Extract the relevant slice of pay periods. array_slice gracefully handles cases
        // where the requested slice size exceeds the number of available elements.
        return array_slice($surroundingPeriods, $startIndex, $this->numPayPeriods);
    }

     private function formatPayPeriodsForNavigator($periods) {
        $formatted = [];
        foreach ($periods as $period) {
            $start = $period['start_date'];
            $end = $period['end_date'];
            $week1End = $start->clone()->addDays(6);

            $formatted[] = [
                'payPeriodLabel' => $start->format('M j') . ' - ' . $end->format('M j'),
                'payPeriodStartDate' => $start->toDateString(),
                'payPeriodEndDate'   => $end->toDateString(),
                'weeks' => [
                    [
                        'weekLabel' => $start->format('M j') . ' - ' . $week1End->format('M j'),
                        'startDate' => $start->toDateString(),
                        'endDate'   => $week1End->toDateString(),
                    ],
                    [
                        'weekLabel' => $week1End->clone()->addDay()->format('M j') . ' - ' . $end->format('M j'),
                        'startDate' => $week1End->clone()->addDay()->toDateString(),
                        'endDate'   => $end->toDateString(),
                    ]
                ]
            ];
        }
        return $formatted;
    }

    /**
     * Fetches and formats timesheet data for a given week.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request) {
        // Stage 1: Initial Validation
        try {
            $validatedData = $request->validate([
                'startDate'          => 'required|date_format:Y-m-d',
                'endDate'            => 'required|date_format:Y-m-d',
                'weekNum'            => 'required|string',
                'payPeriodLabel'     => 'required|string',
                'payPeriodStartDate' => 'required|date_format:Y-m-d',
                'payPeriodEndDate'   => 'required|date_format:Y-m-d',
                'sequenceNum'        => 'required|integer'
            ]);

            $user = Auth::user();
            $user = User::where('email', 'ploughran@ceg-engineers.com')->first(); // TODO: remove
            $startDate = Carbon::parse($validatedData['startDate'])->startOfDay();
            $endDate = Carbon::parse($validatedData['endDate'])->endOfDay();
            $ppStartDate = Carbon::parse($validatedData['payPeriodStartDate'])->startOfDay();
            $ppEndDate = Carbon::parse($validatedData['payPeriodEndDate'])->endOfDay();
            $weekNum = $validatedData['weekNum'];
            $formattedPayPeriod = $validatedData['payPeriodLabel'];
        }
        catch (\Exception $e) {
            Log::error('Input parsing from data-bridge failed in Timesheet getData() Stage 1:' . $e->getMessage(), [
                    'requestData' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);
            $data = [ 
                'timesheetData' => ApiResponse::error("Validation Failed" . $e->getMessage()),
                'statsData'     => ApiResponse::error("Validation Failed" . $e->getMessage()),
                'sequenceNum'   => $validatedData['sequenceNum']
            ];
            return response()->json($data, 200); 
        }

        // No shared data gathering, straight into stage 3

        // Stage 3: Individual Components:

        // Timesheet Data
        try {
            // 1. Get raw timesheet data as before
            $rawTimesheetData = $this->getRawTimesheetData($user, $startDate, $endDate);

            // 2. Get all pinned projects for the user
            $pinnedProjects = $this->getPinnedProjects($user);

            // 3. Merge pinned projects that have no hours
            $mergedTimesheetData = $this->mergePinnedProjects($rawTimesheetData, $pinnedProjects);

            // 4. get Project/Sub/Activity Code dropdown options
            $dropdownData = $this->getDropdownData();

            // 5. format data
            $formattedTimesheetData = $this->processTimesheetData(
                $user, 
                $startDate, 
                $endDate, 
                $mergedTimesheetData, // Use the merged data
                $weekNum, 
                $formattedPayPeriod, 
                $ppStartDate, 
                $ppEndDate,
                $dropdownData
            );

            $timesheetData = ApiResponse::success($formattedTimesheetData);
        }
        catch (\Exception $e) {
            Log::error('Error in retireiving and processing data for timesheet ' . $e->getMessage(), [
                'requestData' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $timesheetData = ApiResponse::error('Error in calculating retireiving and processing data for timesheet ' . $e->getMessage());
        }
        
        // Stat Tiles Data
        try {
            $today = Carbon::today();
            $payPeriodsDoc = GlobalDoc::where('name', 'Pay-Periods')->firstOrFail();
            $surroundingPayPeriods = $this->getSurroundingPayPeriods($payPeriodsDoc, $today);

            $currentPayPeriodIndex = intdiv($this->numPayPeriods, 2);
            $currentPayPeriod = $surroundingPayPeriods[$currentPayPeriodIndex];
            $previousPayPeriod = $surroundingPayPeriods[$currentPayPeriodIndex - 1];

            // variables to be returned
            $prevPayPeriodStatus = ApiResponse::success($this->getPrevPayPeriodStatus($user, $previousPayPeriod));
            $daysLeftInPayPeriod = ApiResponse::success($this->getDaysLeftInPayPeriod($today, $currentPayPeriod));
            $currentPayPeriodHours = ApiResponse::success($this->getCurrentPayPeriodHours($user, $currentPayPeriod));
        }
        catch (\Exception $e) {
           Log::error('Error in retireiving and processing data for stat tiles' . $e->getMessage(), [
                'requestData' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            $prevPayPeriodStatus = ApiResponse::error('Error in retireiving and processing data for stat tiles' . $e->getMessage());
            $daysLeftInPayPeriod = ApiResponse::error('Error in retireiving and processing data for stat tiles' . $e->getMessage());
            $currentPayPeriodHours = ApiResponse::error('Error in retireiving and processing data for stat tiles' . $e->getMessage());
        }

        $response = [ 
            'timesheetData'         => $timesheetData, 
            'prevPayPeriodStatus'   => $prevPayPeriodStatus,
            'daysLeftInPayPeriod'   => $daysLeftInPayPeriod,
            'currentPayPeriodHours' => $currentPayPeriodHours,
            'sequenceNum'   => $validatedData['sequenceNum']
        ];

        return response()->json($response, 200); 
    }

    private function getRawTimesheetData($user, $startDate, $endDate) {
        // Fetch raw hour entries for the user and week
        $pipeline = [
            // Stage 1: Match the initial documents (same as before)
            ['$match' => [
                'user_email' => $user->email,
                'date' => [
                    '$gte' => new \MongoDB\BSON\UTCDateTime($startDate->getTimestamp() * 1000),
                    '$lte' => new \MongoDB\BSON\UTCDateTime($endDate->getTimestamp() * 1000),
                ],
            ]],
            
            // Stage 2: Join with the pinned_projects collection
            ['$lookup' => [
                'from' => 'pinned_projects',
                'let' => [
                    'user_email' => '$user_email',
                    'project_code' => '$project_code',
                    'sub_project' => '$sub_project',
                    'activity_code' => '$activity_code',
                ],
                'pipeline' => [
                    ['$match' => [
                        '$expr' => [
                            '$and' => [
                                ['$eq' => ['$user_email', '$$user_email']],
                                ['$eq' => ['$project_code', '$$project_code']],
                                ['$eq' => ['$sub_project', '$$sub_project']],
                                ['$eq' => ['$activity_code', '$$activity_code']],
                            ]
                        ]
                    ]],
                    ['$limit' => 1] // Optimization: stop after finding one match
                ],
                'as' => 'pinned_project_info',
            ]],

            // Stage 3: Add a boolean field based on the lookup result
            ['$addFields' => [
                'is_pinned' => ['$gt' => [['$size' => '$pinned_project_info'], 0]],
            ]],

            // Stage 4: Group by project identifiers and pivot hours by date
            ['$group' => [
                '_id' => [
                    'project_code' => '$project_code',
                    'sub_project' => '$sub_project',
                    'activity_code' => '$activity_code',
                ],
                // Use $first to carry the is_pinned status to the grouped document
                'is_pinned' => ['$first' => '$is_pinned'],
                'daily_hours' => ['$push' => ['date' => '$date', 'hours' => '$hours']]
            ]],
            
            // Stage 5: Project into a more usable format
            ['$project' => [
                '_id' => 0,
                'project_code' => '$_id.project_code',
                'sub_project' => '$_id.sub_project',
                'activity_code' => '$_id.activity_code',
                'is_pinned' => '$is_pinned', // Include the new field in the final output
                'daily_hours' => '$daily_hours',
            ]],
        ];

        $rawTimesheetData = Hour::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        })->toArray();

        return $rawTimesheetData;
    }

    private function getPinnedProjects($user) {
    // Assuming you have a PinnedProject model. Adjust if using DB facade.
    return PinnedProject::where('user_email', $user->email)
        ->get(['project_code', 'sub_project', 'activity_code'])
        ->map(function ($item) {
            // Create a unique key for easy comparison
            $item->key = $item->project_code . '|' . $item->sub_project . '|' . $item->activity_code;
            return $item;
        });
    }

    private function mergePinnedProjects($rawTimesheetData, $pinnedProjects) {
        // Create a set of keys for existing timesheet entries for quick lookup
        $existingEntries = collect($rawTimesheetData)->mapWithKeys(function ($entry) {
            $key = $entry['project_code'] . '|' . $entry['sub_project'] . '|' . $entry['activity_code'];
            return [$key => true];
        });

        // Iterate through pinned projects and add them if they don't exist in the timesheet data
        foreach ($pinnedProjects as $pinnedProject) {
            if (!isset($existingEntries[$pinnedProject->key])) {
                $rawTimesheetData[] = [
                    'project_code' => $pinnedProject->project_code,
                    'sub_project' => $pinnedProject->sub_project,
                    'activity_code' => $pinnedProject->activity_code,
                    'is_pinned' => true,
                    'daily_hours' => [], // No hours for this period
                ];
            }
        }

        return $rawTimesheetData;
    }

    private function processTimesheetData($user, $startDate, $endDate, $hourEntries, $weekNum, $formattedPayPeriod, $payPeriodStartDate, $payPeriodEndDate, $dropdownData) {   
        $dateHeaders = [];
        $dateMap = [];
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $i => $date) {
            $dateHeaders[] = [
                'day' => $date->format('D'),
                'date' => $date->format('m/d'),
                'isWeekend' => $date->isWeekend(),
            ];
            $dateMap[$date->toDateString()] = $i;
        }

        $timesheetRows = [];

        foreach ($hourEntries as $entry) {
            $hours = [];
            // Initialize hours array correctly based on dateHeaders
            foreach($dateHeaders as $header) {
                $hours[] = ['value' => 0, 'isWeekend' => $header['isWeekend']];
            }
            $rowTotal = 0;

            foreach ($entry['daily_hours'] as $daily) {
                $dayDate = Carbon::parse($daily['date']->toDateTime());
                $dayIndex = $dateMap[$dayDate->toDateString()] ?? null;

                if ($dayIndex !== null) {
                    $hoursValue = (float) $daily['hours'];
                    $hours[$dayIndex]['value'] = $hoursValue;
                    $rowTotal += $hoursValue;
                }
            }

            $timesheetRows[] = [
                'rowId' => uniqid(),
                'project_code' => $entry['project_code'],
                'sub_project' => $entry['sub_project'],
                'activity_code' => $entry['activity_code'],
                'is_pinned' => $entry['is_pinned'],
                'hours' => $hours,
                'rowTotal' => $rowTotal,
            ];
        }
        // Sort the timesheet rows using the new function
        $sortedTimesheetRows = $this->sortTimesheetRows($timesheetRows);

        $formattedData = [
            'headerInfo' => [
                'weekNum'      => $weekNum,
                'payPeriodLabel' => 'of Pay Period (' . $formattedPayPeriod . ')',
                'currentStartDate' => $startDate->toDateString(),
            ],
            'dateHeaders'    => $dateHeaders,
            'timesheetRows'  => $sortedTimesheetRows,
            'dropdownData'   => $dropdownData,
            'payPeriodTotal' => $this->getTotalHoursForPeriod($user, $payPeriodStartDate, $payPeriodEndDate)
        ];
        
        return $formattedData;
    }

    private function sortTimesheetRows($timesheetRows) {
        usort($timesheetRows, function ($a, $b) {
            // 1. Primary Sort: Pinned projects first (descending order)
            $pinnedComparison = $b['is_pinned'] <=> $a['is_pinned'];
            if ($pinnedComparison !== 0) {
                return $pinnedComparison;
            }

            // 2. Secondary Sort: Project code alphabetically (ascending)
            $projectCodeComparison = $a['project_code'] <=> $b['project_code'];
            if ($projectCodeComparison !== 0) {
                return $projectCodeComparison;
            }

            // 3. Tertiary Sort: Sub-project alphabetically (ascending)
            $subProjectComparison = $a['sub_project'] <=> $b['sub_project'];
            if ($subProjectComparison !== 0) {
                return $subProjectComparison;
            }

            // 4. Quaternary Sort: Activity code alphabetically (ascending)
            return $a['activity_code'] <=> $b['activity_code'];
        });

        return $timesheetRows;
    }
/**
 * Retrieves hierarchical dropdown data for projects, sub-projects, and activity codes.
 *
 * @return array Nested associative array structured as:
 * [
 *     'PROJ001' => [
 *         'project_name' => 'Project Alpha',
 *         'sub_projects' => [
 *             'Phase 1' => ['ACT001', 'ACT002', 'ACT003'],
 *             'Phase 2' => ['ACT004', 'ACT005']
 *         ]
 *     ],
 *     'PROJ002' => [
 *         'project_name' => 'Project Beta',
 *         'sub_projects' => [
 *             'Design' => ['ACT010'],
 *             'Development' => ['ACT011', 'ACT012']
 *         ]
 *     ]
 * ]
 */
 private function getDropdownData() {
    // 1. Fetch all projects from the database.
    $projects = Project::where('visibleInTimesheet', true) // Filter 1: Only visible projects
                       ->whereNotNull('projectcode')       // Filter 2: Remove NULL project codes
                       ->where('projectcode', '!=', '')    // Filter 3: Remove empty string project codes
                       ->get();

    $dropdownData = [];
    $projectCodes = $projects->pluck('projectcode')->unique()->all();
    $subProjectNames = $projects->pluck('sub-projects')->flatten()->unique()->all();
    $allSubProjects = SubProject::whereIn('projectcode', $projectCodes)
                                ->whereIn('projectname', $subProjectNames)
                                ->get()
                                ->keyBy(function ($item) {
                                    return $item['projectcode'] . '-' . $item['projectname'];
                                });
    // 4. We iterate through each project to build the final nested associative array.
    foreach ($projects as $project) {
        $projectCode = $project->projectcode;
        $projectName = !empty($project->projectname) ? $project->projectname : "Name Unknown";
       
        $dropdownData[$projectCode]["sub_projects"] = [];
        $dropdownData[$projectCode]["project_name"] = $projectName;
        // Check if the project has any sub-projects listed.
        if (!empty($project->{'sub-projects'})) {
            foreach ($project->{'sub-projects'} as $subProjectName) {
                $lookupKey = $projectCode . '-' . $subProjectName;
                // Check if a corresponding sub-project was found in our earlier query.
                if (isset($allSubProjects[$lookupKey])) {
                    $subProject = $allSubProjects[$lookupKey];
                   
                    // Sort activity codes alphabetically
                    $activityCodes = $subProject->activity_codes;
                    sort($activityCodes);
                    $dropdownData[$projectCode]["sub_projects"][$subProjectName] = $activityCodes;
                }
            }
            // Sort sub-projects alphabetically
            ksort($dropdownData[$projectCode]["sub_projects"]);
        }
    }
    
    // Sort projects alphabetically by project code
    ksort($dropdownData);
    
    return $dropdownData;
    }

    private function getPrevPayPeriodStatus($user, $period) {
        $totalHours = $this->getTotalHoursForPeriod($user, $period['start_date'], $period['end_date']);
        $prevPayPeriodStatus = $totalHours >= 80 ? 'Complete' : 'Incomplete';
        
        return $prevPayPeriodStatus;
    }

    private function getDaysLeftInPayPeriod($today, $currentPayPeriod) {
        $daysLeftInPayPeriod = (int) ($today->diffInDays($currentPayPeriod['end_date'], absolute:false) + 1);
        return $daysLeftInPayPeriod;
    }

    private function getCurrentPayPeriodHours($user, $currentPayPeriod) {
        return $currentPayPeriodHours = $this->getTotalHoursForPeriod($user, $currentPayPeriod['start_date'], $currentPayPeriod['end_date']);
    }

    private function getTotalHoursForPeriod($user, $startDate, $endDate) {
        $pipeline = [
            ['$match' => [
                'user_email' => $user->email,
                'date' => [
                    '$gte' => new \MongoDB\BSON\UTCDateTime($startDate->getTimestamp() * 1000),
                    '$lte' => new \MongoDB\BSON\UTCDateTime($endDate->getTimestamp() * 1000),
                ]
            ]],
            ['$group' => [
                '_id' => null,
                'total_hours' => ['$sum' => '$hours']
            ]]
        ];
        
        $result = Hour::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        })->toArray();

        return $result[0]['total_hours'] ?? 0;
    }

     /**
     * Fetches unique project structures from previous weeks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentRows(Request $request) {
        // Stage 1: Initial Validation
        try {
            $validatedData = $request->validate([
                'referenceDate' => 'required|date_format:Y-m-d',
                'weeksBack'     => 'required|integer|min:1|max:2',
            ]);

            $referenceDate = Carbon::parse($validatedData['referenceDate'])->startOfDay();
            $weeksBack = (int) $validatedData['weeksBack'];

            // Calculate target range
            $targetStartDate = $referenceDate->copy()->subWeeks($weeksBack);
            $targetEndDate = $targetStartDate->copy()->addDays(6)->endOfDay();
            
            $user = Auth::user(); 
            $user = User::where('email', 'ploughran@ceg-engineers.com')->first(); // Keep your dev override if needed
        } catch (\Exception $e) {
             Log::error('Validation failed in getRecentRows: ' . $e->getMessage(), [
                    'requestData' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);
             return response()->json(['recentRows' => ApiResponse::error("Validation Failed")], 200);
        }

        // Stage 2: Shared Data (None needed for this specific lightweight request)

        // Stage 3: Individual Components
        try {
            // Reuse existing logic to get raw data
            $rawPreviousData = $this->getRawTimesheetData($user, $targetStartDate, $targetEndDate);
            $historicalProjectCodes = collect($rawPreviousData)->pluck('project_code')->unique()->values();

            $validProjectCodes = Project::whereIn('projectcode', $historicalProjectCodes) // In case previois week has codes that are no longer valid
            ->where('visibleInTimesheet', true)
            ->pluck('projectcode')
            ->toArray();

            // Process into unique rows (structure only, no hours)
            $uniqueRows = [];
            $seenKeys = [];

            foreach ($rawPreviousData as $entry) {
                 // 3. Skip this entry if the project code is not in our valid list
                if (!in_array($entry['project_code'], $validProjectCodes)) {
                    continue;
                }
                $key = $entry['project_code'] . '|' . $entry['sub_project'] . '|' . $entry['activity_code'];

                if (!in_array($key, $seenKeys)) {
                    $seenKeys[] = $key;
                    $uniqueRows[] = [
                        'project_code' => $entry['project_code'],
                        'sub_project'  => $entry['sub_project'],
                        'activity_code'=> $entry['activity_code'],
                        // We do not copy is_pinned here, relying on the current user's current pin state logic in frontend 
                        // or defaulting to false allows the user to pin them manually if desired.
                    ];
                }
            }
            
            $recentRows = ApiResponse::success($uniqueRows);

        } catch (\Exception $e) {
                Log::error('Error in getRecentRows: ' . $e->getMessage(), [
                    'requestData' => $request->all(),
                    'trace' => $e->getTraceAsString(),
                ]);
            $recentRows = ApiResponse::error("Unable to retrieve recent rows " . $e->getMessage());
        }

        return response()->json(['recentRows' => $recentRows], 200);
    }

    // ===================================================================
    // SAVETIMESHEET
    // ===================================================================

    /**
     * Saves Timesheet from user request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
        public function saveTimesheet(Request $request) {
        // Stage 1: Validation
        try {
            $validatedData = $request->validate([
                'headerInfo.currentStartDate'  => 'required|date_format:Y-m-d',
                'timesheetRows'                => 'present|array',
                'timesheetRows.*.project_code' => 'required|string',
                'timesheetRows.*.sub_project'  => 'present|string|nullable',
                'timesheetRows.*.activity_code'=> 'present|string|nullable',
                'timesheetRows.*.is_pinned'    => 'required|boolean',
                'timesheetRows.*.hours'        => 'required|array|min:7|max:7',
                'timesheetRows.*.hours.*.value'=> 'required|numeric|min:0|max:24',
            ]);

            $user = Auth::user();
            $user = User::where('email', 'ploughran@ceg-engineers.com')->first(); // TODO: remove dev override
            
            $startDate = Carbon::parse($validatedData['headerInfo']['currentStartDate'])->startOfDay();
            $endDate = $startDate->copy()->addDays(6)->endOfDay();

            $rows = $validatedData['timesheetRows'];

        } catch (\Exception $e) {
             Log::error('Validation failed in saveTimesheet: ' . $e->getMessage(), [
                'requestData' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(ApiResult::error("Validation Failed: " . $e->getMessage()), 200);
        }

        // Stage 2: Processing (Prepare data for Bulk Insert)
        try {
            $newHourDocuments = [];
            $newPinnedProjects = [];
            $seenPins = []; 
            $now = new \MongoDB\BSON\UTCDateTime(now());

            foreach ($rows as $row) {
                // 1. Prepare Pinned Projects
                if ($row['is_pinned']) {
                    // Create composite key to ensure uniqueness in the pinned list
                    $pinKey = $row['project_code'] . '|' . ($row['sub_project'] ?? '') . '|' . ($row['activity_code'] ?? '');
                    
                    if (!in_array($pinKey, $seenPins)) {
                        $seenPins[] = $pinKey;
                        $newPinnedProjects[] = [
                            'user_email'    => $user->email,
                            'project_code'  => $row['project_code'],
                            'sub_project'   => $row['sub_project'],
                            'activity_code' => $row['activity_code'],
                            'updated_at'    => $now,
                            'created_at'    => $now,
                        ];
                    }
                }

                // 2. Prepare Hour Entries (Skip rows with no project code)
                if (empty($row['project_code'])) continue;

                foreach ($row['hours'] as $index => $dayHour) {
                    $hoursValue = (float) $dayHour['value'];
                    
                    // Only save non-zero entries
                    if ($hoursValue > 0) {
                        $entryDate = $startDate->copy()->addDays($index);
                        
                        $newHourDocuments[] = [
                            'user_email'    => $user->email,
                            'project_code'  => $row['project_code'],
                            'sub_project'   => $row['sub_project'] ?? '',
                            'activity_code' => $row['activity_code'] ?? '',
                            // Manual BSON conversion required for raw insert arrays
                            'date'          => new \MongoDB\BSON\UTCDateTime($entryDate->getTimestamp() * 1000),
                            'hours'         => $hoursValue,
                            'updated_at'    => $now,
                            'created_at'    => $now,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Data Preparation failed in saveTimesheet: ' . $e->getMessage());
            return response()->json(ApiResult::error("Error preparing data for save."), 200);
        }

        // Stage 3: Transaction execution
        try {
            // Using DB facade to start transaction, but Eloquent for queries where possible
            DB::connection('mongodb')->transaction(function () use ($user, $startDate, $endDate, $newHourDocuments, $newPinnedProjects) {
                
                // 1. Delete existing Hours for this User within this Date Range
                // We use Eloquent here so it handles the Date -> BSON conversion for the query automatically
                Hour::where('user_email', $user->email)
                    ->where('date', '>=', $startDate)
                    ->where('date', '<=', $endDate)
                    ->delete();

                // 2. Delete ALL existing Pinned Projects for this user.
                PinnedProject::where('user_email', $user->email)->delete();

                // 3. Bulk Insert New Hours
                if (!empty($newHourDocuments)) {
                    Hour::insert($newHourDocuments);
                }

                // 4. Bulk Insert New Pinned Projects
                if (!empty($newPinnedProjects)) {
                    PinnedProject::insert($newPinnedProjects);
                }
            }); 
            return response()->json(ApiResult::success(), 200);

        } catch (\Exception $e) {
            Log::error('Transaction failed in saveTimesheet: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(ApiResult::error("Database Transaction Failed: " . $e->getMessage()), 200);
        }
    }
}
