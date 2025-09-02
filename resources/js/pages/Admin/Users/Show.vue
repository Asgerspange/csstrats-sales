<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { GrantAccessDialog } from '@/components/admin/users';

const props = defineProps<{
    user: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/admin/users' },
    { title: props.user?.name || props.user?.id, href: '/admin/users/' + props.user.id },
];
</script>

<template>
    <Head title="Customer Details" />
    <AppLayout :breadcrumbs>
        <div class="flex flex-col gap-6 p-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-3xl font-bold text-gray-600">
                        {{ user.name?.charAt(0) }}
                    </div>
                    <h1 class="text-xl font-bold mt-4">{{ user.name }}</h1>
                    <p class="text-gray-500">{{ user.email }}</p>
                    <span
                        class="mt-3 px-3 py-1 text-sm font-medium rounded-full"
                        :class="user.subscribed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                        {{ user.subscribed ? 'Subscribed' : 'Not Subscribed' }}
                    </span>
                </div>

                <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-500">User ID</h2>
                        <p class="font-mono">{{ user.id }}</p>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-500">Granted Access</h2>
                        <template v-if="user.has_granted_access">
                            <p>Granted at {{ new Date(user.granted_access?.granted_at).toLocaleDateString() }}</p>
                            <p>Ends at {{ new Date(user.granted_access?.expires_at).toLocaleDateString() }}</p>
                            <p class="text-sm text-gray-500">Max Teams: {{ user.granted_access?.max_teams }}</p>
                            <p class="text-sm text-gray-500">Max Members: {{ user.granted_access?.max_members }}</p>
                            <p class="text-sm text-gray-500">Max Stratbooks: {{ user.granted_access?.max_stratbooks }}</p>
                            <p class="text-md text-gray-500">Granter</p>
                            <p>{{ user.granted_access?.granted_by?.name }}</p>
                        </template>
                        <template v-else>
                            <GrantAccessDialog />
                        </template>
                    </div>
                    <!-- <div>
                        <h2 class="text-sm font-semibold text-gray-500">Balance</h2>
                        <p>{{ props.customer.balance }}</p>
                    </div> -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-500">Created</h2>
                        <p>{{ new Date(user.created_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-2">Teams</h2>
                    <p class="text-gray-700">
                        Nørre Aaby Esport
                    </p>
                    <p class="text-gray-700">
                        Nørre Aaby Esport
                    </p>
                    <p class="text-gray-700">
                        Nørre Aaby Esport
                    </p>
                </div>

                <!-- <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-2">Subscriptions</h2>
                    <div v-if="props.customer.subscriptions.length" class="space-y-3">
                        <div
                            v-for="sub in props.customer.subscriptions"
                            :key="sub.id"
                            class="p-4 border rounded-lg bg-gray-50"
                        >
                            <div class="flex items-center justify-between">
                                <p class="font-medium">Plan ID: {{ sub.plan.id }}</p>
                                <span
                                    class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                    :class="sub.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                >
                                    {{ sub.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">
                                Interval: {{ sub.plan.interval_count }} {{ sub.plan.interval }}<br />
                                Amount: {{ (sub.plan.amount / 100).toFixed(2) }} {{ sub.plan.currency.toUpperCase() }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Current Period:
                                {{ new Date(sub.current_period_start * 1000).toLocaleDateString() }} -
                                {{ new Date(sub.current_period_end * 1000).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 italic">No subscriptions found.</p>
                </div> -->
            </div>

            <!-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-2">Invoices</h2>
                    <InvoiceTable :invoices="props.customer.invoices" />
                </div>
            </div> -->
        </div>
    </AppLayout>
</template>
