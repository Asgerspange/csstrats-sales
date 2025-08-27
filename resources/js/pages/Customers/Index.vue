<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { CustomerTable, CustomerFilters } from '@/components/sales/customers';
    import { ref } from 'vue';

    import { type BreadcrumbItem } from '@/types';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        DropdownMenu,
        DropdownMenuCheckboxItem,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from "@/components/ui/dropdown-menu"

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Customers',
            href: '/sales/customers',
        },
    ];

    const filters = ref({
        searchTerm: '',
        subscribedOnly: false,
        currencies: [{ currency: 'usd', active: true }, { currency: 'eur', active: true }]
    });

    const columns = ref([
        {
            'column': 'name',
            'header': 'Name',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'email',
            'header': 'Email',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'cus_id',
            'header': 'Stripe ID',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'subscribed',
            'header': 'Subscribed',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'currency',
            'header': 'Currency',
            'active': true,
            'direction': 'asc'
        }
    ])
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Customers</h1>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button class="btn btn-primary">Actions</button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuCheckboxItem checked>Export CSV</DropdownMenuCheckboxItem>
                        <DropdownMenuCheckboxItem>Import CSV</DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <CustomerFilters 
                v-model:filters="filters" 
                v-model:columns="columns" 
                />
            <CustomerTable :filters :columns />
        </div>
    </AppLayout>
</template>