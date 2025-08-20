<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"
import {
    getCoreRowModel,
    getPaginationRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

import { usePage, router } from "@inertiajs/vue3"
import { computed, ref } from 'vue';

import { useToast } from '@/components/ui/toast/use-toast'
const { toast } = useToast()

interface Mail {
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
}

interface Column {
    column: string;
    header: string;
    active: boolean;
    direction: string;
}

interface Filters {
    searchTerm: string;
}

const props = defineProps<{
    filters: Filters;
    columns: Array<Column>;
    mails: Array<Mail>;
}>();

const page = usePage();
const mails = computed(() => props.mails || []);
const filteredMails = computed(() => {
    let result = (mails.value || []) as Mail[];

    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(mail =>
            mail.subject?.toLowerCase().includes(search) ||
            mail.sender?.name?.toLowerCase().includes(search) ||
            mail.sender?.email?.toLowerCase().includes(search) ||
            mail.recipients.some(recipient => 
                recipient.recipient_email.toLowerCase().includes(search)
            )
        );
    }

    return result;
});

const tableColumns = computed<ColumnDef<Mail>[]>(() => {
    return props.columns
        .filter(col => col.active)
        .map(col => ({
            accessorKey: col.column,
            header: col.header,
            cell: ({ row }) => {
                const value = row.getValue(col.column);
                
                // Handle special cases for display
                if (col.column === 'recipient_count') {
                    return row.original.recipients?.length || 0;
                }
                
                if (col.column === 'status') {
                    return row.original.status.charAt(0).toUpperCase() + row.original.status.slice(1);
                }
                
                if (col.column === 'sent_at' && row.original.sent_at) {
                    return new Date(row.original.sent_at).toLocaleDateString();
                }
                
                if (col.column === 'created_at') {
                    return new Date(row.original.created_at).toLocaleDateString();
                }
                
                return value;
            }
        }));
});

const table = useVueTable({
    get data() { return filteredMails.value; },
    get columns() { return tableColumns.value; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
        pagination: {
            pageSize: 10,
        },
    },
});

const handleRowClick = (mail: Mail) => {
    router.visit(route('mails.show', mail.id));
};

const deleteMail = (id: number) => {
    // Implementation for deleting mail
    fetch(`/mails/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    }).then(response => {
        if (response.ok) {
            toast({
                title: 'Mail Deleted',
                description: 'The mail has been deleted successfully.',
                variant: 'success'
            });

            router.reload({ only: ['mails'] });
        } else {
            toast({
                title: 'Failed to Delete Mail',
                description: 'The mail could not be deleted.',
                variant: 'destructive'
            });
            console.error('Failed to delete mail');
        }
    });
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead 
                    v-for="header in headerGroup.headers" 
                    :key="header.id"
                >
                    {{ header.isPlaceholder ? '' : header.column.columnDef.header }}
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="table.getRowModel().rows.length">
                <TableRow 
                    v-for="row in table.getRowModel().rows" 
                    :key="row.id"
                    @click="handleRowClick(row.original)"
                    class="cursor-pointer hover:bg-gray-100"
                >
                    <TableCell 
                        v-for="cell in row.getVisibleCells()" 
                        :key="cell.id"
                    >
                        {{ cell.getValue() }}
                    </TableCell>
                    <TableCell>
                        <Button size="sm" variant="outline" @click.stop="deleteMail(row.original.id)">
                            Delete
                        </Button>
                    </TableCell>
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell :colspan="tableColumns.length" class="h-24 text-center">
                    No mails found.
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <div class="flex items-center justify-between space-x-2 py-4">
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-700">Rows per page:</span>
            <select 
                :value="table.getState().pagination.pageSize" 
                @change="table.setPageSize(Number($event.target.value))"
                class="border rounded px-2 py-1"
            >
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-700">
                Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
                ({{ table.getFilteredRowModel().rows.length }} total rows)
            </span>
            
            <div class="flex space-x-1">
                <button 
                    @click="table.setPageIndex(0)" 
                    :disabled="!table.getCanPreviousPage()"
                    class="px-2 py-1 border rounded disabled:opacity-50"
                >
                    First
                </button>
                <button 
                    @click="table.previousPage()" 
                    :disabled="!table.getCanPreviousPage()"
                    class="px-2 py-1 border rounded disabled:opacity-50"
                >
                    Previous
                </button>
                <button 
                    @click="table.nextPage()" 
                    :disabled="!table.getCanNextPage()"
                    class="px-2 py-1 border rounded disabled:opacity-50"
                >
                    Next
                </button>
                <button 
                    @click="table.setPageIndex(table.getPageCount() - 1)" 
                    :disabled="!table.getCanNextPage()"
                    class="px-2 py-1 border rounded disabled:opacity-50"
                >
                    Last
                </button>
            </div>
        </div>
    </div>
</template>
