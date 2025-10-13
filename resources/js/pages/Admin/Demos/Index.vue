<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { DemoTable, DemoFilters } from '@/components/admin/demos';
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
            title: 'Demos',
            href: '/admin/demos',
        },
    ];

    const filters = ref({
        searchTerm: '',
    });

    const columns = ref([
        {
            'column': 'id',
            'header': 'ID',
            'active': true,
        },
        {
            'column': 'name',
            'header': 'Name',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'map',
            'header': 'Map',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'owner',
            'header': 'Owner',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'version',
            'header': 'Version',
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
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Demos</h1>
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
            <DemoFilters 
                v-model:filters="filters" 
                v-model:columns="columns" 
                />
            <DemoTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>