<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem } from '@/types';
    import { Head } from '@inertiajs/vue3';

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Customers',
            href: '/customers',
        },
        {
            title: 'Customer Details',
            href: '/customers/1',
        },
    ];

</script>

<template>
    <Head title="Customer Details" />
    <AppLayout :breadcrumbs>
        <h1>Customer Details</h1>
    </AppLayout>
</template>