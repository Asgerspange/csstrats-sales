<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed, ref } from 'vue';

type Payment = { date: string; amount: number; status?: string; customer_id?: string; subscription_id?: string; coupon?: string | null };

const { props } = usePage();
const upcomingPayments = props.upcomingPayments as Payment[];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing/calendar',
    },
];

const toDate = (d: string | Date) => new Date(d);
const today = new Date();

const filteredPayments = computed(() => {
    if (!upcomingPayments) return [];
    const now = new Date();
    const startYear = now.getMonth() >= 4 ? now.getFullYear() : now.getFullYear() - 1;
    const periodStart = new Date(startYear, 4, 1); // May 1
    const periodEnd = new Date(startYear + 1, 3, 30); // April 30 next year

    return upcomingPayments.filter(p => {
        const d = toDate(p.date);
        return d >= periodStart && d <= periodEnd;
    });
});


const paymentsMap = computed(() => {
    const map: Record<string, Payment[]> = {};
    for (const p of filteredPayments.value ?? []) {
        const key = toISODate(p.date);
        if (!map[key]) map[key] = [];
        map[key].push(p);
    }
    return map;
});


const months = computed(() => buildMonths(today, 12));

function buildMonths(start: Date) {
    const out: any[] = [];
    const startYear = start.getMonth() >= 4 ? start.getFullYear() : start.getFullYear() - 1; 
    // If current month >= May (4), start this May, otherwise last year’s May

    for (let i = 0; i < 12; i++) {
        const year = startYear + Math.floor((4 + i) / 12); // shift years as needed
        const month = (4 + i) % 12; // May = 4, June = 5, ..., April = 3

        const first = new Date(year, month, 1);
        const label = first.toLocaleString('en-US', { month: 'long' });
        const leading = mondayIndex(first);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells: any[] = [];

        for (let b = 0; b < leading; b++) cells.push({ key: `b-${year}-${month}-b${b}`, blank: true });

        for (let d = 1; d <= daysInMonth; d++) {
            const date = new Date(year, month, d);
            const key = toISODate(date);
            const payments = paymentsMap.value[key] ?? [];
            const isToday = isSameDate(date, today);

            cells.push({
                key,
                blank: false,
                day: d,
                date,
                payments,
                isToday,
                title: payments.length ? `${payments.length} payments` : formatDate(key),
            });
        }

        out.push({ key: `${year}-${month}`, label, year, month, cells });
    }

    return out;
}


function mondayIndex(d: Date) {
    const w = d.getDay();
    return (w + 6) % 7;
}

function isSameDate(a: Date, b: Date) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
}

function toISODate(d: string | Date) {
    const dt = new Date(d);
    dt.setHours(0, 0, 0, 0);
    const y = dt.getFullYear();
    const m = `${dt.getMonth() + 1}`.padStart(2, '0');
    const day = `${dt.getDate()}`.padStart(2, '0');
    return `${y}-${m}-${day}`;
}


function formatDate(d: string | Date) {
    const dt = typeof d === 'string' ? new Date(d) : d;
    return dt.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatCurrency(amount: number, currency: string) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency }).format(amount / 100);
}

const todayLabel = computed(() => formatDate(today));

function dayCellClass(cell: any) {
    const base = 'border rounded-lg transition-colors flex items-center justify-center text-sm relative';

    const hasPayment = cell.payments.length > 0;
    const hasYearlyPayment = cell.payments.some((p: Payment & { interval?: string }) => p.interval === 'year');
    const hasSemiAnnualPayment = cell.payments.some((p: Payment & { interval?: string }) => p.interval === 'semi-annually');

    // Both yearly and semi-annual payments
    if (hasYearlyPayment && hasSemiAnnualPayment && cell.isToday)
        return `${base} bg-gradient-to-r from-purple-600/40 via-blue-600/40 to-emerald-800/50 border-emerald-600 text-white dark:text-emerald-200`;
    if (hasYearlyPayment && hasSemiAnnualPayment)
        return `${base} bg-[linear-gradient(to_right,rgba(239,68,68,0.2)_0%,rgba(239,68,68,0.2)_50%,rgba(30,64,175,0.3)_50%,rgba(30,64,175,0.3)_100%)] border-emerald-600 text-emerald-900 dark:text-emerald-200`;

    if (hasYearlyPayment && cell.isToday)
        return `${base} bg-purple-600/40 border-emerald-600 text-white dark:text-emerald-200`;
    if (hasYearlyPayment)
        return `${base} bg-red-600/20 border-emerald-600 text-emerald-900 dark:text-emerald-200`;
    if (hasSemiAnnualPayment && cell.isToday)
        return `${base} bg-blue-600/40 border-blue-600 text-white dark:text-blue-200`;
    if (hasSemiAnnualPayment)
        return `${base} bg-blue-500/20 border-blue-500 text-blue-900 dark:text-blue-200`;
    if (hasPayment && cell.isToday)
        return `${base} bg-emerald-800/50 border-emerald-500 text-emerald-900 dark:text-emerald-200`;
    if (hasPayment)
        return `${base} bg-emerald-500/20 border-emerald-500 text-emerald-900 dark:text-emerald-200`;
    if (cell.isToday)
        return `${base} bg-neutral-200 dark:bg-neutral-700 border-neutral-400 dark:border-neutral-600`;

    return `${base} bg-neutral-100 dark:bg-neutral-800 border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300`;
}

const currentPage = ref(1);
const pageSize = 15;

const sortedPayments = computed(() => {
    return [...filteredPayments.value].sort((a, b) => toDate(a.date).getTime() - toDate(b.date).getTime());
});

const totalPages = computed(() => Math.ceil(sortedPayments.value.length / pageSize));
const paginatedPayments = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    return sortedPayments.value.slice(start, start + pageSize);
});

function nextPage() {
    if (currentPage.value < totalPages.value) currentPage.value++;
}

function prevPage() {
    if (currentPage.value > 1) currentPage.value--;
}

const monthlyPay = computed(() => {
    const map: Record<string, Record<string, number>> = {}; // { 'YYYY-MM': { USD: 1200, EUR: 500 } }
    for (const payment of filteredPayments.value) {
        if (payment.status !== 'paid' && payment.status !== 'upcoming') continue;
        const date = new Date(payment.date);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (!map[key]) map[key] = {};
        const currency = payment.currency || 'USD';
        map[key][currency] = (map[key][currency] || 0) + payment.amount;
    }
    return map;
});


console.log(monthlyPay.value)
</script>

<template>
    <Head title="Billing Calendar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xl font-semibold">Subscription Payment Calendar</CardTitle>
                        <Badge variant="secondary">Today: {{ todayLabel }}</Badge>
                    </div>
                    <CardDescription>All previous and upcoming Stripe payments from the accounting period May to April</CardDescription>
                </CardHeader>
                <CardContent class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        
                        <div v-for="m in months" :key="m.key" class="rounded-xl border border-border bg-card p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-medium">{{ m.label }}</div>
                                <div class="text-xs text-muted-foreground">{{ m.year }}</div>
                            </div>
                            <div class="flex gap-1 text-xs text-emerald-700 dark:text-emerald-300 mb-1">
                                <span v-for="(amount, currency) in monthlyPay[`${m.year}-${String(m.month + 1).padStart(2, '0')}`]" :key="currency">
                                    {{ formatCurrency(amount, currency) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-7 text-xs text-muted-foreground mb-1">
                                <div class="text-center">Mon</div>
                                <div class="text-center">Tue</div>
                                <div class="text-center">Wed</div>
                                <div class="text-center">Thu</div>
                                <div class="text-center">Fri</div>
                                <div class="text-center">Sat</div>
                                <div class="text-center">Sun</div>
                            </div>
                            
                            <div class="grid grid-cols-7 gap-1">
                                <div v-for="cell in m.cells" :key="cell.key" class="aspect-square">
                                    <div v-if="cell.blank" class="h-full"></div>
                                    <Link
                                        v-else
                                        :href="`/billing/date/${toISODate(cell.date)}`"
                                        class="block h-full"
                                    >
                                        <div :class="dayCellClass(cell)" :title="cell.title">
                                            <span>{{ cell.day }}</span>
                                            <div v-if="cell.payments.length" class="absolute -bottom-1 inset-x-0 flex justify-center">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="text-sm text-muted-foreground">Upcoming charges (all customers)</div>
                        <div class="overflow-hidden rounded-xl border border-border">
                            <table class="min-w-full text-sm">
                                <thead class="bg-muted">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium">Date</th>
                                        <th class="px-4 py-3 text-left font-medium">Customer</th>
                                        <th class="px-4 py-3 text-left font-medium">Amount</th>
                                        <th class="px-4 py-3 text-left font-medium">Coupon</th>
                                        <th class="px-4 py-3 text-left font-medium">Status</th>
                                        <th class="px-4 py-3 text-left font-medium">Interval</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in paginatedPayments" :key="p.date + p.customer_id" class="border-t border-border">
                                        <td class="px-4 py-3">{{ formatDate(p.date) }}</td>
                                        <td class="px-4 py-3 text-muted-foreground">{{ p.customer_id }}</td>
                                        <td class="px-4 py-3 font-semibold">{{ formatCurrency(p.amount, p.currency) }}</td>
                                        <td class="px-4 py-3">
                                            <Badge v-if="p.coupon" variant="secondary">{{ p.coupon }}</Badge>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="p.status === 'paid' ? 'outline' : 'default'">{{ p.status ?? 'scheduled' }}</Badge>
                                        </td>
                                        <td class="px-4 py-3">
                                            <Badge :variant="p.interval === 'month' ? 'outline' : 'default'">{{ p.interval }}</Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex justify-between items-center mt-2 text-sm">
                            <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border rounded disabled:opacity-50">Previous</button>
                            <div>Page {{ currentPage }} of {{ totalPages }}</div>
                            <button @click="nextPage" :disabled="currentPage === totalPages" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
