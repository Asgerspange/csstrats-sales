<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ref } from 'vue';
    import DataTable from '@/volt/DataTable.vue';
    import Column from 'primevue/column';
    import Button from '@/volt/Button.vue';
    import CreateAffiliateDialog from './CreateAffiliateDialog.vue';
    import { type BreadcrumbItem } from '@/types';

    const props = defineProps<{
        affiliate: {
            id: number;
            name: string;
            coupon_relation: object;
            email: string;
            bank_account: string | null;
            commission_rate: number;
            balance: number;
            min_payout_amount: number;
            total_earned: number;
            total_paid: number;
            status: string;
            last_update: string;
            access_token: string;
        };
    }>();

    console.log(props.affiliate)

    const affiliate = ref(props.affiliate || {});

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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Affiliate</h1>
            </div>
            {{ affiliate }}
        </div>
    </AppLayout>
</template>