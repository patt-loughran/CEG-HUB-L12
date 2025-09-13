<?php

namespace App\Http\Controllers\Time;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Hour;
use App\Models\GlobalDoc;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use App\Helpers\ErrorLogger;

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
            $dateNavigatorProps = $this->formatPayPeriodsForNavigator($surroundingPayPeriods);

        } catch (Exception $e) {
            Log::error('TimesheetController error in index(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return view('errors.500', ['message' => 'Could not load timesheet data. Please try again later.']);
        }

        return view('time.timesheet', [
            'dateNavigatorData'  => $dateNavigatorProps,
        ]);
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
            throw new Exception("no relevant pay-periods found");
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
            throw new Exception('todays date not found in available pay-periods');
        }

        // Calculate the starting index for our slice. We want 6 periods before the current one.
        // max(0, ...) ensures the index is never negative.
        $startIndex = max(0, $currentIndex - 6);

        // We need a total of 13 periods (6 before + 1 current + 6 after).
        $length = 13;

        // Extract the relevant slice of pay periods. array_slice gracefully handles cases
        // where the requested slice size exceeds the number of available elements.
        return array_slice($surroundingPeriods, $startIndex, $length);
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
        sleep(2);
        try {
            [$user, $startDate, $endDate, $weekNum, $formattedPayPeriod] = $this->validateRequest($request);
            $timesheetData = $this->getTimesheetData($user, $startDate, $endDate, $weekNum, $formattedPayPeriod, $request);
            $statsData = $this->getStatsData($user, $request);

            return response()->json([ 
            'timesheetData' => $timesheetData, 
            'statsData' => $statsData
            ]); 
        }
        catch (Exception $e) {
            ErrorLogger::logOnly($e, 'Error in GetData() in Timesheet Controller', $request,[]);
            return view('errors.500', ['message' => 'Could not load timesheet data. Please try again later.']);
        }
    }
    
    // ===================================================================
    // GETDATA HELPER FUNCTIONS
    // ===================================================================

    private function validateRequest($request) {
        try {
             $validatedData = $request->validate([
                'startDate'          => 'required|date_format:Y-m-d',
                'endDate'            => 'required|date_format:Y-m-d',
                'weekNum'            => 'required|string',
                'payPeriodLabel'     => 'required|string'
            ]);

            $user = Auth::user();
            $user = User::where('email', 'ploughran@ceg-engineers.com')->first();
            $startDate = Carbon::parse($validatedData['startDate'])->startOfDay();
            $endDate = Carbon::parse($validatedData['endDate'])->endOfDay();

            return [$user, $startDate, $endDate, $validatedData['weekNum'], $validatedData['payPeriodLabel']];
        }
        catch (Exception $e) {
             ErrorLogger::logAndRethrow($e, 'Request validation failed in validateRequest method in TimesheetController', $request,[]);
        }
    }
    private function getTimesheetData($user, $startDate, $endDate, $weekNum, $formattedPayPeriod, $request) {
        try {
            $rawTimesheetData = $this->getRawTimesheetData($user, $startDate, $endDate);
            $formattedTimesheetData = $this->processTimesheetData($user, $startDate, $endDate, $rawTimesheetData, $weekNum, $formattedPayPeriod);

            return ["success" => true, "data" => $formattedTimesheetData, "errors" => null];
        }
        catch (Exception $e) {
            ErrorLogger::logAndRethrow($e, 'Error in getting data for timesheet in TimesheetController in getData()', $request,[]);
            return ["success" => false, "data" => null, "errors" => $e->getMessage()];
        }
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

    private function processTimesheetData($user, $startDate, $endDate, $hourEntries, $weekNum, $formattedPayPeriod) {   
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
        $dailyTotals = array_fill(0, 7, 0);

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
                    $dailyTotals[$dayIndex] += $hoursValue;
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

        $formattedData = [
            'headerInfo' => [
                'weekNum'      => $weekNum,
                'payPeriodLabel' => 'of Pay Period (' . $formattedPayPeriod . ')',
            ],
            'dateHeaders'    => $dateHeaders,
            'timesheetRows'  => $timesheetRows
        ];
        
        return $formattedData;
    }

    private function getStatsData($user, $request) {
        try {
        $today = Carbon::today();
        $payPeriodsDoc = GlobalDoc::where('name', 'Pay-Periods')->firstOrFail();
        $surroundingPayPeriods = $this->getSurroundingPayPeriods($payPeriodsDoc, $today);

        $currentPayPeriodIndex = intdiv($this->numPayPeriods, 2);
        $currentPayPeriod = $surroundingPayPeriods[$currentPayPeriodIndex];
        $previousPayPeriod = $surroundingPayPeriods[$currentPayPeriodIndex - 1];

        // Calculate initial stats
        return [
            'prevPayPeriodStatus'   => $this->getPrevPayPeriodStatus($user, $previousPayPeriod), // data can be "submitted" or "late"
            'daysLeftInPayPeriod'   => $this->getDaysLeftInPayPeriod($today, $currentPayPeriod),
            'currentPayPeriodHours' => $this->getCurrentPayPeriodHours($user, $currentPayPeriod)
        ];
        }
        catch (Exception $e) {
            ErrorLogger::logAndRethrow($e, 'Error in getting data for stat tiles for timesheet', $request,[]);
           return [
                'prevPayPeriodStatus'   => ["success" => false, "data" => null, "errors" => $e->getMessage()],
                'daysLeftInPayPeriod'   => ["success" => false, "data" => null, "errors" => $e->getMessage()],
                'currentPayPeriodHours' => ["success" => false, "data" => null, "errors" => $e->getMessage()]
            ];
        }
    }

    private function getPrevPayPeriodStatus($user, $period) {
        try {
            $totalHours = $this->getTotalHoursForPeriod($user, $period['start_date'], $period['end_date']);
            $prevPayPeriodStatus = $totalHours >= 80 ? 'Submitted' : 'Late';
            
            return [
                "success" => true,
                "data"    => $prevPayPeriodStatus,
                "errors"  => null
            ];
        } catch (Exception $e) {
            ErrorLogger::logAndRethrow($e,'Failed to get previous pay-period status ');
            return [
                'success' => false,
                'data'    => null,
                'error'   => $e->getMessage()
            ];
        }
    }

    private function getDaysLeftInPayPeriod($today, $currentPayPeriod) {
        try {
            $daysLeftInPayPeriod = (int) ($today->diffInDays($currentPayPeriod['end_date'], absolute:false) + 1);
            return [
                "success" => true,
                "data"    => $daysLeftInPayPeriod,
                "errors"  => null
            ];
        } catch (Exception $e) {
            Log::warning('Failed to get days left in current pay-period ' . $e->getMessage());
            return [
                'success' => false,
                'data'    => null,
                'error'   => $e->getMessage()
            ];
        }
    }

    private function getCurrentPayPeriodHours($user, $currentPayPeriod) {
        try {
            $currentPayPeriodHours = $this->getTotalHoursForPeriod($user, $currentPayPeriod['start_date'], $currentPayPeriod['end_date']);
        }
        catch (Exception $e) {
            
        }
        return $currentPayPeriodHours;
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
}
