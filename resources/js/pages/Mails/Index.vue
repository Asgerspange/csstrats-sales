<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { MailTable, MailFilters } from '@/components/mails';
import { ref, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mails',
        href: '/mails',
    },
];

const props = defineProps<{
    mails: Array<{
        id: number;
        subject: string;
        sender: {
            name: string;
            email: string;
        };
        status: string;
        created_at: string;
        sent_at: string | null;
        recipients: Array<{
            recipient_email: string;
            type: string;
        }>;
    }>;
}>();

const filters = ref({
    searchTerm: '',
});

const columns = ref([
    {
        'column': 'subject',
        'header': 'Subject',
        'active': true,
        'direction': 'asc'
    },
    {
        'column': 'sender.name',
        'header': 'Sender',
        'active': true,
        'direction': 'asc'
    },
    {
        'column': 'status',
        'header': 'Status',
        'active': true,
        'direction': 'asc'
    },
    {
        'column': 'recipient_count',
        'header': 'Recipients',
        'active': true,
        'direction': 'asc'
    },
    {
        'column': 'sent_at',
        'header': 'Sent At',
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
    <Head title="Mails" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Mails</h1>
                <div class="flex gap-4">
                    <Link href="/mails/create" class="btn btn-primary">New Email</Link>
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
            <MailFilters
                v-model:filters="filters"
                v-model:columns="columns"
            />
            <MailTable :filters="filters" :columns="columns" :mails="props.mails" />
        </div>
    </AppLayout>
</template>
