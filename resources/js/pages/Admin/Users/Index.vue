<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { UserTable, UserFilters } from '@/components/admin/users';
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
            title: 'Users',
            href: '/admin/users',
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
            'column': 'email',
            'header': 'Email',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'stripe_id',
            'header': 'Stripe ID',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'has_granted_access',
            'header': 'Has Granted Access',
            'active': true,
            'direction': 'asc'
        },
        {
            'column': 'is_admin',
            'header': 'Is Admin',
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
                <h1 class="text-2xl font-bold">Users</h1>
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
            <UserFilters 
                v-model:filters="filters" 
                v-model:columns="columns" 
                />
            <UserTable :filters="filters" :columns="columns" />
        </div>
    </AppLayout>
</template>