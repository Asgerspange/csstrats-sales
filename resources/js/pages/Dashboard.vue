<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import {
    RevenueCard,
    SubscriptionCard,
    SalesCard,
    MonthlyRevenueCard,
    RevenueGraph,
    TransactionTimeline,
    PackageTable
} from '@/components/dashboard';

const { props } = usePage();
console.log('Dashboard props:', props);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="grid auto-rows-min gap-4 md:grid-cols-4">
                <div class="relative">
                    <RevenueCard />
                </div>
                <div class="relative">
                    <SubscriptionCard />
                </div>
                <div class="relative">
                    <MonthlyRevenueCard />
                </div>
                <div class="relative">
                    <SalesCard />
                </div>
            </div>
            <div class="grid auto-row-min gap-4 lg:grid-cols-2 md:grid-cols-1 sm:grid-cols-1">
                <div class="relative">
                    <RevenueGraph />
                </div>
                <div class="relative">
                    <TransactionTimeline />
                </div>
            </div>
            <div class="relative">
                <PackageTable />
            </div>
        </div>
    </AppLayout>
</template>
