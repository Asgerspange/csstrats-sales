<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer as CustomerModel;
use App\Models\Subscription as SubscriptionModel;
use App\Models\invoices as InvoicesModel;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionItem;
use Stripe\Customer;
use Stripe\Invoice;

class fetchStripeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-stripe-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // $this->getCustomers();
        //$this->getSubscriptions();
        $this->getInvoices();
    }

    private function getCustomers()
    {
        $allCustomers = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = Customer::all($params);
            foreach ($response->data as $customer) {
                $allCustomers[] = $customer;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total customers fetched: ' . count($allCustomers));
        $this->info('Inserting customers into the database...');

        foreach ($allCustomers as $customer) {
            $existing = CustomerModel::where('cus_id', $customer->id)->first();

            $data = [
                'object' => $customer->object,
                'address' => $customer->address,
                'balance' => $customer->balance,
                'created' => $customer->created,
                'currency' => $customer->currency,
                'description' => $customer->description,
                'email' => $customer->email,
                'invoice_prefix' => $customer->invoice_prefix,
                'invoice_settings' => $customer->invoice_settings,
                'metadata' => $customer->metadata,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];

            if ($existing) {
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                CustomerModel::create(array_merge(['cus_id' => $customer->id], $data));
            }
        }

        $this->info('Customers inserted successfully.');
    }

    private function getSubscriptions()
    {
        $allSubscriptions = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = Subscription::all($params);
            foreach ($response->data as $subscription) {
                $allSubscriptions[] = $subscription;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total subscriptions fetched: ' . count($allSubscriptions));
        $this->info('Inserting subscriptions into the database...');

        foreach ($allSubscriptions as $subscription) {
            $existing = SubscriptionModel::where('sub_id', $subscription->id)->first();

            $data = [
                'object' => $subscription->object,
                'currency' => $subscription->currency,
                'customer' => $subscription->customer,
                'latest_invoice' => $subscription->latest_invoice,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'coupon' => $subscription?->discount?->coupon,
                'created' => $subscription->created
            ];

            if ($existing) {
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                SubscriptionModel::create(array_merge(['sub_id' => $subscription->id], $data));
            }
        }

        $this->info('Subscriptions inserted successfully.');
    }

    private function getInvoices()
    {
        $allInvoices = [];
        $hasMore = true;
        $startingAfter = null;

        while ($hasMore) {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }
            $response = \Stripe\Invoice::all($params);
            foreach ($response->data as $invoice) {
                $allInvoices[] = $invoice;
            }
            $hasMore = $response->has_more;
            if ($hasMore && count($response->data) > 0) {
                $startingAfter = end($response->data)->id;
            }
        }

        $this->info('Total invoices fetched: ' . count($allInvoices));
        $this->info('Inserting invoices into the database...');

        foreach ($allInvoices as $invoice) {
            $existing = InvoicesModel::where('invoice_id', $invoice->id)->first();

            $data = [
                'billing_reason' => $invoice->billing_reason,
                'collection_method' => $invoice->collection_method,
                'currency' => $invoice->currency,
                'customer' => $invoice->customer,
                'discounts' => $invoice->discounts,
                'invoice_pdf' => $invoice->invoice_pdf,
                'data' => $invoice->lines['data'],
                'sub_id' => $invoice->parent['subscription_details']['subscription'] ?? null,
                'subtotal' => $invoice->subtotal,
                'subtotal_excluding_tax' => $invoice->subtotal_excluding_tax,
                'status_transitions' => $invoice->status_transitions,
                'created' => $invoice->created
            ];

            if ($existing) {
                $needsUpdate = false;
                foreach ($data as $key => $value) {
                    if ($existing->$key != $value) {
                        $needsUpdate = true;
                        break;
                    }
                }

                if ($needsUpdate) {
                    $existing->update($data);
                }
            } else {
                InvoicesModel::create(array_merge(['invoice_id' => $invoice->id], $data));
            }
        }

        $this->info('Invoices inserted successfully.');
    }
}