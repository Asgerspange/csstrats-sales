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
    type SortingState,
    type ColumnDef,
    getSortedRowModel,
    FlexRender
} from '@tanstack/vue-table';
import { Button } from '@/components/ui/button'
import { ArrowUpDown } from 'lucide-vue-next'
import { ref, computed, h } from 'vue';
import { valueUpdater } from '@/lib/utils'
import { usePage } from "@inertiajs/vue3"
const { subscriptions } = usePage().props
const sorting = ref<SortingState>([]);

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
    columns: Array<Column>;
    filters: Filters;
}>();

interface Subscription {
    id: number,
    sub_id: string;
    currency: string;
    latest_invoice: string;
    plan: object;
    items: object;
    status: string,
    current_period_start: string;
    current_period_end: string;
    coupon: object;
    created: string;
}

const tableColumns = computed<ColumnDef<Subscription>[]>(() => {
    return props.columns
        .filter(col => !['customer_relation'].includes(col.column))
        .map(col => {
            const isSortable = ['id', 'created', 'status', 'product_id'].includes(col.column)

            return {
                accessorKey: col.column,
                header: ({ column }) => {
                    if (col.header === 'Current_period_start') {
                        col.header = 'Current Period'
                    }
                    if (!isSortable) return h('span', col.header);
                    if (col.header === 'Product_id') {
                        col.header = 'Product'
                    }
                    return h(Button, {
                        variant: 'ghost',
                        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    }, () => [col.header, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
                },
                accessorFn: row => {
                    // For sorting purposes, return the raw values that can be properly sorted
                    if (col.column === 'created') return row.created * 1000; // Convert to milliseconds for proper date sorting
                    if (col.column === 'customer') {
                        return row.customer_relation?.name || 'N/A';
                    }
                    if (col.column === 'product_id') {
                        const name = row.package?.name;
                        const price = row.package?.price;
                        const currency = row.currency;
                        if (!name || !price || !currency) return row.plan.product;
                        return `${name} ${price} ${currency}`;    
                    }
                    if (col.column === 'current_period_start') {
                        return row.current_period_start * 1000; // Return timestamp for sorting
                    }
                    return row[col.column]
                },
                cell: ({ getValue, row }) => {
                    // For display purposes, format the values appropriately
                    const value = getValue();
                    if (col.column === 'created') {
                        return new Date(row.original.created * 1000).toLocaleDateString();
                    }
                    if (col.column === 'current_period_start') {
                        return new Date(row.original.current_period_start * 1000).toLocaleDateString() + ' - ' + new Date(row.original.current_period_end * 1000).toLocaleDateString();
                    }
                    return value;
                },
                enableSorting: isSortable,
            }
        })
});

const filteredSubscriptions = computed(() => {
    let result = (subscriptions || []) as Subscription[];

    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(sub =>
            sub.sub_id?.toLowerCase().includes(search) ||
            sub.customer_relation?.email?.toLowerCase().includes(search) ||
            sub.customer_relation?.name?.toLowerCase().includes(search) ||
            sub.customer_relation?.cus_id?.toLowerCase().includes(search) ||
            sub.package?.name?.toLowerCase().includes(search)
        );
    }

    if (props.filters.activeOnly) {
        result = result.filter(sub => sub.status === 'active');
    }

    if (props.filters.pastDueOnly) {
        result = result.filter(sub => sub.status === 'past_due');
    }
    return result || [];
});
const table = useVueTable({
    get data() { return filteredSubscriptions.value; },
    get columns() { return tableColumns.value; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    state: {
        get sorting() { return sorting.value },
    },
    initialState: {
        pagination: {
            pageSize: 10,
        },
        sorting: []
    },
});
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <TableHead 
                    v-for="header in headerGroup.headers" 
                    :key="header.id"
                >
                    <FlexRender
                        v-if="!header.isPlaceholder"
                        :render="header.column.columnDef.header"
                        :props="{ column: header.column }"
                    />
                </TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-if="table.getRowModel().rows.length">
                <TableRow 
                    v-for="row in table.getRowModel().rows" 
                    :key="row.id"
                    class="cursor-pointer hover:bg-gray-100"
                >
                    <TableCell 
                        v-for="cell in row.getVisibleCells()" 
                        :key="cell.id"
                    >
                        <FlexRender
                            :render="cell.column.columnDef.cell || (() => cell.getValue())"
                            :props="{ getValue: () => cell.getValue(), row }"
                        />
                    </TableCell>
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell :colspan="tableColumns.length" class="h-24 text-center">
                    No subscriptions found.
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <div class="flex items-center justify-between space-x-2 py-4">
        
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