<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ref, computed } from 'vue';
    import DataTable from '@/volt/DataTable.vue';
    import Column from 'primevue/column'
    import Button from '@/volt/Button.vue';
    import CreateAffiliateDialog from './CreateAffiliateDialog.vue';
    import { type BreadcrumbItem } from '@/types';

    const props = defineProps<{
        affiliate: object;
        invoices: any[] | object;
    }>();

    // Convert the invoices object to a proper array
    const invoicesArray = computed(() => {
        // If it's already an array, return it
        if (Array.isArray(props.invoices)) {
            return props.invoices;
        }
        
        // If it's an object, convert it to an array
        // Object.values() will get all values and create a proper array
        return Object.values(props.invoices);
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Sales',
            href: '/sales',
        },
        {
            title: 'Affiliates',
            href: '/sales/affiliates',
        },
        {
            title: props.affiliate.name,
            href: `/sales/affiliates/${props.affiliate.id}`,
        },
    ];

    const formatCurrency = (value: number, shouldDivide: boolean = true) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'DKK',
            minimumFractionDigits: 2
        }).format(shouldDivide ? value / 100 : value);
    };

    // Format date for display
    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const calcuateCommission = (amount: number) => {
        return (amount * props.affiliate.commission_rate) / 100;
    };

    const totalEarned = computed(() => {
        return invoicesArray.value.reduce((total, invoice) => {
            return total + calcuateCommission(invoice.totalAfterFeesAndVat);
        }, 0);
    });

    const goalAmount = props.affiliate.min_payout_amount;
    const progressPercentage = computed(() => {
        const percentage = (totalEarned.value / 100 / goalAmount) * 100;
        return percentage
    });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Affiliate: {{ affiliate.name }}</h1>

                <div class="flex items-center gap-4">
                    <!-- Custom Tailwind Progress Bar -->
                    <div class="w-64">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700 font-medium">Earnings</span>
                            <span class="text-gray-600">{{ formatCurrency(totalEarned) }} / {{ formatCurrency(goalAmount, false) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 relative overflow-hidden">
                            <div 
                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-300 ease-out"
                                :style="`width: ${progressPercentage}%`"
                            >
                                <div class="h-full w-full bg-gradient-to-r from-transparent via-white/20 to-transparent animate-pulse"></div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-gray-500">0%</span>
                            <span class="text-gray-500 font-medium">{{ progressPercentage.toFixed(1) }}%</span>
                            <span class="text-gray-500">100%</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <DataTable :value="invoicesArray" class="w-full" paginator :rows="10">
                <Column field="id" header="Invoice ID" sortable></Column>
                <Column field="subtotal_excluding_tax" header="Amount" sortable>
                    <template #body="{ data }">
                        {{ formatCurrency(data.subtotal_excluding_tax) }}
                    </template>
                </Column>
                <Column field="totalAfterFeesAndVat" header="Total After Fees & VAT" sortable>
                    <template #body="{ data }">
                        {{ formatCurrency(data.totalAfterFeesAndVat) }}
                    </template>
                </Column>
                <Column field="commission" header="Commission" sortable>
                    <template #body="{ data }">
                        {{ formatCurrency(calcuateCommission(data.totalAfterFeesAndVat)) }}
                    </template>
                </Column>
                <Column field="coupon" header="Coupon Code" sortable></Column>
                <Column field="created_at" header="Created At" sortable>
                    <template #body="{ data }">
                        {{ formatDate(data.created_at) }}
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>