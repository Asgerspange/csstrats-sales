<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import DataTable from '@/volt/DataTable.vue';
    import Column from 'primevue/column';
    import Button from '@/volt/Button.vue';
    import CreateAffiliateDialog from './CreateAffiliateDialog.vue';
    import { type BreadcrumbItem } from '@/types';

    const props = defineProps<{
        affiliates: Array<{
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
        }>;
    }>();

    console.log(props.affiliates)

    const affiliates = ref(props.affiliates || []);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Sales',
            href: '/sales',
        },
        {
            title: 'Affiliates',
            href: '/sales/affiliates',
        },
    ];

    function deleteAffiliate(affiliateId: number) {
        fetch(`/sales/affiliates/${affiliateId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content,
                'Accept': 'application/json',
            },
        })
            .then(response => {
                if (response.ok) {
                    affiliates.value = affiliates.value.filter(a => a.id !== affiliateId);
                }
            });
    }

    function onRowSelect(event: any) {
        console.log('e')
        const affiliateId = event.data.id;
        router.visit(`/sales/affiliates/${affiliateId}`);
    }
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Affiliates</h1>
                <CreateAffiliateDialog />
            </div>
            <DataTable :value="affiliates" class="w-full" selectionMode="single" @rowSelect="onRowSelect">
                <Column field="name" header="Name" sortable></Column>
                <Column 
                    header="Code" 
                    sortable>
                    <template #body="{ data: affiliate }">
                        {{ affiliate.coupon_relation?.code ? affiliate.coupon_relation.code + ' (P)' : affiliate.coupon + ' (C)' || 'N/A' }}
                    </template>
                </Column>
                <Column field="email" header="Email" sortable></Column>
                <Column field="commission_rate" header="Commission Rate (%)" sortable></Column>
                <Column field="balance" header="Balance" sortable></Column>
                <Column field="min_payout_amount" header="Min Payout Amount" sortable></Column>
                <Column field="total_earned" header="Total Earned" sortable></Column>
                <Column field="total_paid" header="Total Paid" sortable></Column>
                <Column field="status" header="Status" sortable></Column>
                <Column field="last_update" header="Last Update" sortable></Column>
                <Column header="Actions">
                    <template #body="{ data: affiliate }">
                        <Button label="Delete" severity="danger" size="small" @click="deleteAffiliate(affiliate.id)" />
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>