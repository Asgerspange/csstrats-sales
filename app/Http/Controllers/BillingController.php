<?php

// app/Http/Controllers/BillingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\StripeClient;
use Carbon\Carbon;

class BillingController extends Controller
{
    public function calendar(Request $request)
    {
        $today = Carbon::now();

        $startYear = $today->month >= 5 ? $today->year : $today->year - 1;
        $periodStart = Carbon::create($startYear, 5, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->addYear()->subDay();

        $invoices = \App\Models\Invoices::with(['customerRelation', 'subscription'])
            ->get();

        $previousPayments = $invoices->map(function ($inv) {
            $subtotal = $inv->subtotal ?? 0;
            if ($inv->discounts) {
                $subtotal -= $inv->data[0]['discount_amounts'][0]['amount'] ?? 0;
            }

            return [
                'date' => $inv->created->format('Y-m-d'),
                'amount' => $subtotal,
                'status' => !empty($inv->status_transitions['paid_at']) ? 'paid' :
                            (!empty($inv->status_transitions['marked_uncollectible_at']) || !empty($inv->status_transitions['voided_at']) ? 'failed' : 'unpaid'),
                'customer_id' => $inv->customer ?? 'Unknown',
                'subscription_id' => $inv->sub_id,
                'interval' => $inv->payment_interval ?? 'semi-annually',
                'currency' => $inv->currency ?? 'USD',
            ];
        })->filter(fn($payment) => $payment['status'] === 'paid');

        $upcomingPayments = collect([]);

        if ($previousPayments->isNotEmpty()) {
            $prevMonth = $today->copy()->subMonth();
            $prevMonthNum = $prevMonth->month;
            $prevMonthYear = $prevMonth->year;
            $todayDay = $today->day;

        $remainingPrevMonthPayments = $previousPayments->filter(fn($p) => 
            Carbon::parse($p['date'])->month === $prevMonthNum &&
            Carbon::parse($p['date'])->year === $prevMonthYear &&
            Carbon::parse($p['date'])->day > $todayDay
        )->values();


        foreach ($remainingPrevMonthPayments as $payment) {
            if ($payment['interval'] !== 'month') {
                continue;
            }
            $day = Carbon::parse($payment['date'])->day;
            $projectedDate = Carbon::create($today->year, $today->month, min($day, Carbon::create($today->year, $today->month)->daysInMonth()));

            $upcomingPayments->push([
                'date' => $projectedDate->format('Y-m-d'),
                'amount' => $payment['amount'],
                'status' => 'upcoming',
                'customer_id' => $payment['customer_id'],
                'subscription_id' => $payment['subscription_id'],
                'interval' => $payment['interval'],
                'currency' => $payment['currency'],
            ]);
        }

        $currentMonthPayments = $previousPayments->filter(function($p) use ($today, $remainingPrevMonthPayments) {
            $date = Carbon::parse($p['date']);
            return $date->month === $today->month && $date->year === $today->year;
        })->merge($remainingPrevMonthPayments)->values();


            $monthIterator = Carbon::create($today->year, $today->month, 1)->addMonth();
            $endMonth = $periodEnd->copy()->startOfMonth();

            while ($monthIterator->lte($endMonth)) {
                foreach ($currentMonthPayments as $payment) {
                    if ($payment['interval'] !== 'month') {
                        continue;
                    }
                    $day = Carbon::parse($payment['date'])->day;
                    $projectedDate = Carbon::create($monthIterator->year, $monthIterator->month, min($day, $monthIterator->daysInMonth()));

                    $upcomingPayments->push([
                        'date' => $projectedDate->format('Y-m-d'),
                        'amount' => $payment['amount'],
                        'status' => 'upcoming',
                        'customer_id' => $payment['customer_id'],
                        'subscription_id' => $payment['subscription_id'],
                        'interval' => $payment['interval'],
                        'currency' => $payment['currency'],
                    ]);
                }
                $monthIterator->addMonth();
            }
        }

        $yearlyInvoicesNotWithinPeriod = $invoices->filter(function($inv) use ($periodStart, $periodEnd) {
            $isYearly = isset($inv->payment_interval) && $inv->payment_interval === 'year';
            if (!$isYearly) return false;

            return $inv->created < $periodStart || $inv->created > $periodEnd;
        });

        $semiAnnualInvoices = $invoices->filter(function($inv) {
            return isset($inv->payment_interval) && $inv->payment_interval === 'semi-annually';
        });


        foreach ($semiAnnualInvoices as $inv) {
            $upcomingPayments->push([
                'date' => $inv->created->addMonths(6)->format('Y-m-d'),
                'amount' => $inv->subtotal,
                'status' => 'upcoming',
                'customer_id' => $inv->customer,
                'subscription_id' => $inv->sub_id,
                'interval' => $inv->payment_interval,
                'currency' => $inv->currency,
            ]);
        }

        //Add 1 year to yearly invoices not within period and add them to upcoming payments
        foreach ($yearlyInvoicesNotWithinPeriod as $inv) {
            $upcomingPayments->push([
                'date' => $inv->created->addYear()->format('Y-m-d'),
                'amount' => $inv->subtotal,
                'status' => 'upcoming',
                'customer_id' => $inv->customer,
                'subscription_id' => $inv->sub_id,
                'interval' => $inv->payment_interval,
                'currency' => $inv->currency,
            ]);
        }
        $payments = $previousPayments->merge($upcomingPayments)->sortBy('date')->values();

        return Inertia::render('Billing/Calendar', [
            'upcomingPayments' => $payments,
        ]);
    }

    public function dayAnalysis(Request $request, $date)
    {
        $day = Carbon::parse($date)->startOfDay();
        $nextDay = $day->copy()->addDay();

        $invoices = \App\Models\Invoices::with(['customerRelation', 'subscription'])
            ->whereBetween('created', [$day, $nextDay])
            ->get();

        $payments = $invoices->map(function ($inv) {
            $customerName = $inv->customerRelation?->name ?? $inv->customer;
            $customerEmail = $inv->customerRelation?->email ?? '—';

            $planName = null;
            if (!empty($inv->data) && isset($inv->data[0]['plan'])) {
                $planName = $inv->data[0]['plan']['nickname'] 
                            ?? $inv->data[0]['description'] 
                            ?? 'Plan';
            } else {
                $planName = $inv->data[0]['description'] ?? 'Plan';
            }

            if (!empty($inv->status_transitions['marked_uncollectible_at']) || !empty($inv->status_transitions['voided_at'])) {
                $status = 'failed';
            } elseif (!empty($inv->status_transitions['paid_at'])) {
                $status = 'paid';
            } else {
                $status = 'unpaid';
            }

            $coupon = null;
            if (!empty($inv->discounts[0])) {
                $coupon = $inv->discounts[0]['name'] ?? null;
            }

            return [
                'subscription_id' => $inv->sub_id,
                'customer_id' => $inv->customer ?? 'Unknown',
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'plan_name' => $planName,
                'amount' => $inv->subtotal ?? 0,
                'status' => $status,
                'coupon' => $coupon,
                'currency' => $inv->currency ?? 'USD',
            ];
        });

        return Inertia::render('Billing/DayAnalysis', [
            'date' => $day->toDateString(),
            'payments' => $payments,
        ]);
    }
}
