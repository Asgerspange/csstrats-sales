<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed, onMounted, ref } from 'vue';
import { Chart, registerables } from 'chart.js';
import { type BreadcrumbItem } from '@/types';

Chart.register(...registerables);

const props = defineProps<{ date: string; payments: any[] }>();

const totalRevenue = computed(() =>
    props.payments.reduce((sum, p) => sum + p.amount, 0)
);

const couponCount = computed(() =>
    props.payments.filter(p => p.coupon).length
);

const planRevenue = computed(() => {
    const map: Record<string, number> = {};
    for (const p of props.payments) {
        map[p.plan_name] = (map[p.plan_name] || 0) + p.amount;
    }
    return map;
});

const customerRevenue = computed(() => {
    const map: Record<string, number> = {};
    for (const p of props.payments) {
        map[p.customer_name] = (map[p.customer_name] || 0) + p.amount;
    }
    return map;
});

const statusCounts = computed(() => ({
    due: props.payments.filter(p => p.status === 'due').length,
    scheduled: props.payments.filter(p => p.status === 'scheduled').length,
}));

// Chart refs
const couponChart = ref<HTMLCanvasElement | null>(null);
const planChart = ref<HTMLCanvasElement | null>(null);
const customerChart = ref<HTMLCanvasElement | null>(null);
const statusChart = ref<HTMLCanvasElement | null>(null);

function chartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    };
}

onMounted(() => {
    if (couponChart.value) {
        new Chart(couponChart.value, {
            type: 'doughnut',
            data: {
                labels: ['With Coupon', 'Without Coupon'],
                datasets: [{
                    data: [couponCount.value, props.payments.length - couponCount.value],
                    backgroundColor: ['#34D399', '#3B82F6'],
                }]
            },
            options: chartOptions()
        });
    }

    if (planChart.value) {
        new Chart(planChart.value, {
            type: 'bar',
            data: {
                labels: Object.keys(planRevenue.value),
                datasets: [{
                    label: 'Revenue by Plan',
                    data: Object.values(planRevenue.value).map(v => v / 100),
                    backgroundColor: '#F59E0B',
                }]
            },
            options: chartOptions()
        });
    }

    if (customerChart.value) {
        new Chart(customerChart.value, {
            type: 'bar',
            data: {
                labels: Object.keys(customerRevenue.value),
                datasets: [{
                    label: 'Revenue per Customer',
                    data: Object.values(customerRevenue.value).map(v => v / 100),
                    backgroundColor: '#10B981',
                }]
            },
            options: { ...chartOptions(), indexAxis: 'y' }
        });
    }

    if (statusChart.value) {
        new Chart(statusChart.value, {
            type: 'pie',
            data: {
                labels: ['Due', 'Scheduled'],
                datasets: [{
                    data: [statusCounts.value.due, statusCounts.value.scheduled],
                    backgroundColor: ['#EF4444', '#3B82F6'],
                }]
            },
            options: chartOptions()
        });
    }
});

function formatCurrency(amount: number, currency: string) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency.toUpperCase() }).format(amount / 100);
}

const revenueByCurrency = computed(() => {
    const map: Record<string, number> = {};
    for (const p of props.payments) {
        map[p.currency] = (map[p.currency] || 0) + p.amount;
    }
    return Object.entries(map); // [['USD', 1200], ['EUR', 500], ...]
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: route('sales.billing.index')
    },
    {
        title: 'Calendar',
        href: route('sales.billing.calendar'),
    },
    {
        title: props.date,
        href: route('sales.billing.day', { date: props.date })
    }
];


</script>

<template>
    <Head :title="`Analysis for ${props.date}`" />

    <AppLayout :breadcrumbs>
        <div class="p-4 space-y-8">
            <Card>
                <CardHeader>
                    <CardTitle>Daily Payment Analysis — {{ props.date }}</CardTitle>
                    <CardDescription>
                        Total Revenue:
                            <span v-for="([currency, amount], index) in revenueByCurrency" :key="currency">
                                <strong>{{ formatCurrency(amount, currency) }}</strong><span v-if="index < revenueByCurrency.length - 1"> · </span>
                            </span>
                            · {{ props.payments.length }} payments

                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-8">

                    <!-- Graphs Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader><CardTitle class="text-sm font-medium">Coupons</CardTitle></CardHeader>
                            <CardContent><div class="h-64"><canvas ref="couponChart"></canvas></div></CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle class="text-sm font-medium">Revenue by Plan</CardTitle></CardHeader>
                            <CardContent><div class="h-64"><canvas ref="planChart"></canvas></div></CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle class="text-sm font-medium">Revenue per Customer</CardTitle></CardHeader>
                            <CardContent>
                                <div class="h-80 overflow-x-auto">
                                    <canvas ref="customerChart"></canvas>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle class="text-sm font-medium">Payment Status</CardTitle></CardHeader>
                            <CardContent><div class="h-64"><canvas ref="statusChart"></canvas></div></CardContent>
                        </Card>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border rounded overflow-hidden">
                            <thead class="bg-muted">
                                <tr>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-left">Email</th>
                                    <th class="px-4 py-2 text-left">Plan</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Coupon</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in props.payments" :key="p.subscription_id" class="border-t border-border">
                                    <td class="px-4 py-2">{{ p.customer_name }}</td>
                                    <td class="px-4 py-2 text-muted-foreground">{{ p.customer_email }}</td>
                                    <td class="px-4 py-2">{{ p.plan_name }}</td>
                                    <td class="px-4 py-2 font-semibold">{{ formatCurrency(p.amount, p.currency) }}</td>
                                    <td class="px-4 py-2">
                                        <Badge v-if="p.coupon" variant="secondary">{{ p.coupon }}</Badge>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <Badge :variant="p.status === 'due' ? 'default' : 'outline'">{{ p.status }}</Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
