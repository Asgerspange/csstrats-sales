<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { SubscriptionTable, SubscriptionFilters } from '@/components/sales/billing/subscriptions';
    import { ref } from 'vue';

    import { type BreadcrumbItem } from '@/types';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        DropdownMenu,
        DropdownMenuCheckboxItem,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from "@/components/ui/dropdown-menu"

    const { props } = usePage();
    console.log(props.subscriptions[0])
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Billing',
            href: '/sales/billing',
        },
        {
            title: 'Subscriptions',
            href: '/sales/billing/subscriptions',
        }
    ];

    const filters = ref({
        searchTerm: '',
        activeOnly: false,
        pastDueOnly: false,
    });

    const excludeKeys = ['object', 'package', 'customer_relation', 'plan', 'items', 'currency', 'coupon', 'current_period_end'];
    const columns = ref(
        Object.keys(props.subscriptions[0] || {})
            .filter(key => !excludeKeys.includes(key))
            .map(key => ({
                column: key,
                header: key.charAt(0).toUpperCase() + key.slice(1),
                active: true,
                direction: 'asc'
            }))
    );
</script>

<template>
    <Head title="Subscriptions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Subscriptions</h1>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button class="btn btn-primary">Actions</button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuCheckboxItem checked>Export CSV</DropdownMenuCheckboxItem>    
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <SubscriptionFilters
                v-model:filters="filters"
                v-model:columns="columns"
            />
            <SubscriptionTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>