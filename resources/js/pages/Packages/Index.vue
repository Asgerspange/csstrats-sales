<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { PackageTable, PackageFilters, CreatePackageDialog } from '@/components/sales/packages';
    import { ref, watch } from 'vue';
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
            title: 'Packages',
            href: '/packages',
        },
    ];

    const props = defineProps(['packages'])

    const filters = ref({
        searchTerm: '',
    });

    const columns = ref([
        {
            'column': 'name',
            'header': 'Name',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'organisation.name',
            'header': 'Organisation',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'price',
            'header': 'Price',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'monthly_price',
            'header': 'Monthly Price',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'max_teams',
            'header': 'Max Teams',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'max_members',
            'header': 'Max Users',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'max_stratbooks',
            'header': 'Max Stratbooks',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'created_at',
            'header': 'Created At',
            'active': true,
            'direction': 'asc'
        }
    ])
</script>

<template>
    <Head title="Packages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Packages</h1>
                <div class="flex gap-4">
                    <CreatePackageDialog />
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
            </div>
            <PackageFilters
                v-model:filters="filters"
                v-model:columns="columns"
            />
            <PackageTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>