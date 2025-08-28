<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { PaidPaymentsCard, UpcomingPaymentsCard, UnpaidPaymentsCard, PaymentsTable } from '@/components/sales/billing';
    import { Calendar, Receipt, DollarSignIcon, User } from 'lucide-vue-next';
    import { type BreadcrumbItem } from '@/types';
    import { Head, Link, usePage } from '@inertiajs/vue3';
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Billing',
            href: '/sales/billing',
        },
    ];

    const { props } = usePage();
        console.log(props)

    const columns = [
        { column: 'customer_id', header: 'Customer ID', active: true, direction: 'asc' },
        { column: 'subscription_id', header: 'Subscription ID', active: true, direction: 'asc' },
        { column: 'amount', header: 'Amount', active: true, direction: 'asc' },
        { column: 'currency', header: 'Currency', active: true, direction: 'asc' },
        { column: 'date', header: 'Date', active: true, direction: 'asc' },
    ];
</script>

<template>
    <Head title="Billing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative">
                    <PaidPaymentsCard />
                </div>
                <div class="relative">
                    <UnpaidPaymentsCard />
                </div>
                <div class="relative">
                    <UpcomingPaymentsCard />
                </div>
            </div>
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- <div class="relative">
                    <Link :href="route('sales.customers.index')" class="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
                        <div class="flex gap-2 justify-center">
                            <User class="h-6 w-6" />
                            Customers
                        </div>
                    </Link>
                </div> -->
                <div class="relative">
                    <Link :href="route('sales.billing.calendar')" class="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
                        <div class="flex gap-2 justify-center">
                            <Calendar class="h-6 w-6" />
                            Calendar
                        </div>
                    </Link>
                </div>
                <div class="relative">
                    <Link :href="route('sales.billing.calendar')" class="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
                        <div class="flex gap-2 justify-center">
                            <Receipt class="h-6 w-6" />
                            Invoices
                        </div>
                    </Link>
                </div>
                <div class="relative">
                    <Link :href="route('sales.billing.subscriptions.index')" class="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
                        <div class="flex gap-2 justify-center">
                            <DollarSignIcon class="h-6 w-6" />
                            Subscriptions
                        </div>
                    </Link>
                </div>
            </div>

            <PaymentsTable :columns="columns" />
        </div>
    </AppLayout>
</template>