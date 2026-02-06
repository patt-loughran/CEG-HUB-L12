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
    /**
     * Display the timesheet page with initial data.
     *
     * @return \Illuminate\View\View
     */
    public function index() {
        try {
            // 1. Fetch the Pay-Periods document
            $doc = GlobalDoc::where('name', 'Pay-Periods')->first();
            $payPeriods = $doc->{'Pay-Periods'};

            $payPeriodsFormatted = [];
            $earliestStart = null;
            $latestEnd = null;
            
            // 2. Normalize Data
            foreach ($payPeriods as $year => $periods) {
                $cleanedPeriods = [];
                foreach ($periods as $period) {
                    // Handle standard Carbon casting 
                    $start = $period['start_date'];
                    $end = $period['end_date'];

                    if ($earliestStart === null || $start->lt($earliestStart)) {
                        $earliestStart = $start;
                    }
                    if ($latestEnd === null || $end->gt($latestEnd)) {
                        $latestEnd = $end;
                    }

                    // Ensure we have standard ISO strings
                    $cleanedPeriods[] = [
                        'start_date' => $start->format('m/d/y H:i:s'),
                        'end_date'   => $end->format('m/d/y H:i:s')
                    ];
                }
                $payPeriodsFormatted[$year] = $cleanedPeriods;
            }

            $startDateCutOff = $earliestStart ? $earliestStart->format('m/d/y H:i:s') : null;
            $endDateCutOff = $latestEnd ? $latestEnd->format('m/d/y H:i:s') : null;

            return view('time.employee-time', [
                'payPeriodData'   => $payPeriodsFormatted,
                'startDateCutOff' => $startDateCutOff,
                'endDateCutOff'   => $endDateCutOff
            ]);

        } catch (\Exception $e) {
            Log::error('EmployeeTimeController error: ' . $e->getMessage());
            return response()->view('errors.500', ['error_message' => $e->getMessage()], 500);
        }
    }
}