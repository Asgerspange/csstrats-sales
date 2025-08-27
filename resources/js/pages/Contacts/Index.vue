<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ContactTable, ContactFilters, CreateContactDialog } from '@/components/sales/contacts';
    import { ref, watch } from 'vue';

    import { Button } from '@/components/ui/button';

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
            title: 'Contacts',
            href: '/contacts',
        },
    ];

    const props = defineProps(['contacts'])

    const filters = ref({
        searchTerm: '',
        danishOnly: false,
        countries: [
            ...Array.from(
                new Set(props.contacts.map(contact => contact.country))
            ).map(country => ({ country, active: true }))
        ]
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
            'column': 'country',
            'header': 'Country',
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
            'column': 'phone',
            'header': 'Phone',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'job',
            'header': 'Job',
            'active': true,
            'direction': 'asc'
        }
    ])
</script>

<template>
    <Head title="Contacts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Contacts</h1>
                <div class="flex gap-4">
                    <CreateContactDialog />
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
            <ContactFilters
                v-model:filters="filters"
                v-model:columns="columns"
            />
            <ContactTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>