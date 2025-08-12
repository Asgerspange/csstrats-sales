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

interface Contact {
    id: number;
    name?: string;
    cvr?: string;
    country?: string;
    address: string;
    type: string;
    created_at: string;
    [key: string]: any;
}

interface Column {
    column: string;
    header: string;
    active: boolean;
    direction: string;
}

interface Filters {
    searchTerm: string;
    subscribedOnly: boolean;
    currencies: Array<{ currency: string, active: boolean }>;
}

const props = defineProps<{
    filters: Filters;
    columns: Array<Column>;
}>();

const page = usePage();
const contacts = computed(() => page.props.contacts || []);
const filteredContacts = computed<Contact[]>(() => {
    let result = (contacts.value || []) as Contact[];

    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(contact =>
            contact.name?.toLowerCase().includes(search) ||
            organisation.cvr?.toLowerCase().includes(search)
        );
    }

    if (props.filters.danishOnly) {
        result = result.filter(contact => contact.country.toLowerCase() === 'dk');
    }

    const activeCountries = props.filters.countries
        .filter(c => c.active)
        .map(c => c.country);

    if (activeCountries.length > 0 && activeCountries.length < props.filters.countries.length) {
        result = result.filter(contact =>
            activeCountries.includes(contact.country)
        );
    }
    
    return result;
});

const tableColumns = computed<ColumnDef<Contact>[]>(() => {
    return props.columns
        .filter(col => col.active)
        .map(col => ({
            accessorKey: col.column,
            header: col.header,
            cell: ({ row }) => {
                const value = row.getValue(col.column);
                return value
            }
        }));
});

const table = useVueTable({
    get data() { return filteredContacts.value; },
    get columns() { return tableColumns.value; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
        pagination: {
            pageSize: 10,
        },
    },
});

const deletingContact = ref<number | null>(null);

const handleRowClick = (contact: Contact) => {
    router.visit(route('contacts.show', contact.id));
};

const deleteContact = (id: number) => {
    deletingContact.value = id;
    fetch(`contacts/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    }).then(response => {
        if (response.ok) {
            toast({
                title: 'Organisation Deleted',
                description: 'The organisation has been deleted successfully.',
                variant: 'success'
            });

            router.reload({ only: ['organisations'] });
        } else {
            toast({
                title: 'Failed to Delete Organisation',
                description: 'The organisation could not be deleted.',
                variant: 'destructive'
            });
            console.error('Failed to delete organisation');
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
                        <Button size="sm" variant="destructive"
                            @click.stop="deleteContact(row.original.id)" :disabled="deletingContact === row.original.id"
                        >
                            <LoaderCircle v-if="deletingContact === row.original.id" class="h-4 w-4 animate-spin" />
                            Delete
                        </Button>
                    </TableCell>
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell :colspan="tableColumns.length" class="h-24 text-center">
                    No organisations found.
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