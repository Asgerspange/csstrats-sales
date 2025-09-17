<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

import { TacticCreateChart, TacticBreakdown } from '@/components/admin/tactics';
const { props } = usePage();
console.log('Dashboard props:', props);
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Tactics',
        href: '/admin/tactics',
    },
    {
        title: 'Statistics',
        href: '/admin/tactics/statistics',
    }
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="grid auto-row-min gap-4 lg:grid-cols-1 md:grid-cols-1 sm:grid-cols-1">
                <div class="relative">
                    <TacticCreateChart />
                </div>
                <div class="relative mt-12">
                    <TacticBreakdown />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
