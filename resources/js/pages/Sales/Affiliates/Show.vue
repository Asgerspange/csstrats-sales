<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ref, computed } from 'vue';
    import DataTable from '@/volt/DataTable.vue';
    import Column from 'primevue/column';
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

    // Format currency for display
    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'DKK',
            minimumFractionDigits: 2
        }).format(value / 100); // Assuming the values are in cents
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Affiliate: {{ affiliate.name }}</h1>
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