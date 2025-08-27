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

import { usePage, router } from "@inertiajs/vue3"
import { computed } from 'vue';

interface Customer {
    id: string;
    name?: string;
    email?: string;
    cus_id?: string;
    subscribed: boolean;
    currency: string;
    created: number;
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

const { props: pageProps } = usePage<{ customers: Customer[] }>();

const filteredCustomers = computed<Customer[]>(() => {
    let result = (pageProps.customers || []) as Customer[];
    
    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(customer =>
            customer.name?.toLowerCase().includes(search) ||
            customer.email?.toLowerCase().includes(search) ||
            customer.cus_id?.toLowerCase().includes(search)
        );
    }
    
    if (props.filters.subscribedOnly) {
        result = result.filter(customer => customer.subscribed === true);
    }
    
    const activeCurrencies = props.filters.currencies
        .filter(c => c.active)
        .map(c => c.currency);
    
    if (activeCurrencies.length > 0 && activeCurrencies.length < props.filters.currencies.length) {
        result = result.filter(customer => 
            activeCurrencies.includes(customer.currency)
        );
    }
    
    return result;
});

const tableColumns = computed<ColumnDef<Customer>[]>(() => {
    return props.columns
        .filter(col => col.active)
        .map(col => ({
            accessorKey: col.column,
            header: col.header,
            cell: ({ row }) => {
                const value = row.getValue(col.column);
                
                if (col.column === 'subscribed') {
                    return value ? 'Yes' : 'No';
                } else {
                    return value;
                }
            }
        }));
});

const table = useVueTable({
    get data() { return filteredCustomers.value; },
    get columns() { return tableColumns.value; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
        pagination: {
            pageSize: 10,
        },
    },
});

const handleRowClick = (customer: Customer) => {
    router.visit(route('sales.customers.show', customer.id));
};
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
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell :colspan="tableColumns.length" class="h-24 text-center">
                    No customers found.
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