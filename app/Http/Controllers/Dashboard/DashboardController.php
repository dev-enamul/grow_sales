<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SalesPayment;
use App\Models\SalesPaymentSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');

        // 1. Total Collection of this month (Actual Received)
        $totalCollectionThisMonth = SalesPayment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->where('status', 1) // 1 = Approved
            ->sum('amount');

        // 2. Expected Collection in this month (Total Demand/Scheduled)
        // Sum of all schedule amounts due in this month
        $totalExpectedCollectionThisMonth = SalesPaymentSchedule::whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // 3. Overdue Collection (Previous months)
        // Logic: (Sum of Schedules Due Before this Month) - (Sum of Payments linked to those Schedules)
        // Step A: Find IDs of schedules due before this month
        // We use query builder for efficiency
        
        $overdueSchedulesQuery = SalesPaymentSchedule::where('due_date', '<', $startOfMonth);
        $totalOverdueScheduled = $overdueSchedulesQuery->sum('amount');
        
        // Step B: Find payments linked to those specific schedules
        // We need the IDs for WhereIn, or use subquery
        $overdueScheduleIds = $overdueSchedulesQuery->pluck('id');
        
        $totalOverduePaid = SalesPayment::whereIn('payment_schedule_id', $overdueScheduleIds)
            ->where('status', 1)
            ->sum('amount');
            
        $overdueCollection = $totalOverdueScheduled - $totalOverduePaid;
        
        // Ensure non-negative (incase of overpayment or data anomaly)
        if ($overdueCollection < 0) {
            $overdueCollection = 0;
        }

        // 4. Total Active Leads (Active Pipeline)
        // Focusing on leads that are in progress
        $activeLeadsCount = Lead::whereIn('status', ['Active', 'Contacted', 'Negotiation', 'Proposal', 'Waiting'])->count();


        return response()->json([
            'status' => true,
            'data' => [
                'total_collection_this_month' => $totalCollectionThisMonth,
                'expected_collection_this_month' => $totalExpectedCollectionThisMonth,
                'overdue_collection' => $overdueCollection,
                'active_leads_count' => $activeLeadsCount
            ]
        ]);
    }

    public function chartData(Request $request)
    {
        $filter = $request->input('filter', 'yearly'); // yearly, monthly, weekly
        
        $leadsData = [];
        $salesData = [];
        $labels = [];
        
        $now = Carbon::now();
        
        if ($filter == 'yearly') {
            // Last 12 Months or Current Year? "Yearly selected thakbe ... 12 month"
            // Let's show Current Year (Jan-Dec).
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
            
            // Labels: Jan, Feb...
            for ($i = 0; $i < 12; $i++) {
                $labels[] = $now->copy()->startOfYear()->addMonths($i)->format('M');
            }
            
            // Query
            $leads = Lead::whereBetween('created_at', [$start, $end])
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->pluck('count', 'month')->toArray();
                
            // Note: Sales model name -> Sales or Sale? Checking imports...
            // Assuming Sales model exists as App\Models\Sales
            $sales = \App\Models\Sales::whereBetween('created_at', [$start, $end])
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->pluck('count', 'month')->toArray();
                
            // Fill data
            for ($i = 1; $i <= 12; $i++) {
                $leadsData[] = $leads[$i] ?? 0;
                $salesData[] = $sales[$i] ?? 0;
            }
            
        } elseif ($filter == 'monthly') {
            // Current Month, split by Weeks
            // 1st Week (1-7), 2nd (8-14), 3rd (15-21), 4th (22-end)
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            
            // We can fetch all and loop or Group by logic.
            // Simple loop is easier to maintain for custom buckets
            $allLeads = Lead::whereBetween('created_at', [$start, $end])->get();
            $allSales = \App\Models\Sales::whereBetween('created_at', [$start, $end])->get();
            
            // Buckets
             $buckets = [
                ['start' => 1, 'end' => 7],
                ['start' => 8, 'end' => 14],
                ['start' => 15, 'end' => 21],
                ['start' => 22, 'end' => 31],
            ];
            
            foreach ($buckets as $b) {
                // Count leads where day is between start and end
                $lCount = $allLeads->filter(function($item) use ($b) {
                    $day = $item->created_at->day;
                    return $day >= $b['start'] && $day <= $b['end'];
                })->count();
                
                $sCount = $allSales->filter(function($item) use ($b) {
                    $day = $item->created_at->day;
                    return $day >= $b['start'] && $day <= $b['end'];
                })->count();
                
                $leadsData[] = $lCount;
                $salesData[] = $sCount;
            }
            
        } elseif ($filter == 'weekly') {
            // Current Week (Daily)
            $start = $now->copy()->startOfWeek(); // default Mon?
            $end = $now->copy()->endOfWeek();
            
            // Labels: Mon, Tue...
            $temp = $start->copy();
            while($temp <= $end) {
                $labels[] = $temp->format('D');
                $temp->addDay();
            }
            
            // Query
            $leads = Lead::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')->toArray();
                
            $sales = \App\Models\Sales::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date')->toArray();
                
            // Fill
            $temp = $start->copy();
            while($temp <= $end) {
                $dateStr = $temp->format('Y-m-d');
                $leadsData[] = $leads[$dateStr] ?? 0;
                $salesData[] = $sales[$dateStr] ?? 0;
                $temp->addDay();
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => [
                'labels' => $labels,
                'series' => [
                    ['name' => 'Leads', 'data' => $leadsData],
                    ['name' => 'Sales', 'data' => $salesData],
                ]
            ]
        ]);
    }

    public function recentActivity()
    {
        // Last 5 payments
        $payments = SalesPayment::with(['sales.lead']) 
            ->where('status', 1)
            ->latest('payment_date')
            ->take(5)
            ->get()
            ->map(function($p) { 
                $name = 'Client';
                if ($p->sales && $p->sales->lead) { 
                }
                return [
                    'id' => $p->id,
                    'amount' => $p->amount,
                    'date' => $p->payment_date,
                    'ref' => $p->transaction_ref ?? 'N/A',
                    'status' => 'Received'
                ];
            });
            
        return response()->json([
            'status' => true,
            'data' => $payments
        ]);
    }


}

