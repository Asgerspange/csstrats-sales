<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { OrganisationTable, OrganisationFilters } from '@/components/organisations';
    import { ref, watch } from 'vue';

    import { Button } from '@/components/ui/button';

    import { DialogTrigger } from "@/components/ui/dialog"
    import { type BreadcrumbItem } from '@/types';
    import { Head, usePage } from '@inertiajs/vue3';
    import {
        DropdownMenu,
        DropdownMenuCheckboxItem,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from "@/components/ui/dropdown-menu"
    import CreateOrganisationDialog from '@/components/organisations/CreateOrganisationDialog.vue';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Organisations',
            href: '/organisations',
        },
    ];

    const props = defineProps(['organisations'])

    const filters = ref({
        searchTerm: '',
        danishOnly: false,
        countries: [...props.organisations.map(org => ({ country: org.country, active: true }))]
    });

    const columns = ref([
        {
            'column': 'name',
            'header': 'Name',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'cvr',
            'header': 'CVR',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'country',
            'header': 'Country',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'address',
            'header': 'Address',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'type',
            'header': 'Type',
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
                <div class="flex gap-4">
                    <CreateOrganisationDialog />
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
            <OrganisationFilters
                v-model:filters="filters"
                v-model:columns="columns"
            />
            <OrganisationTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>