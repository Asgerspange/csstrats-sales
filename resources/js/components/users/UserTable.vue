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
    getSortedRowModel,
    type ColumnDef,
    type SortingState
} from '@tanstack/vue-table';
import { ref, h } from 'vue';

import { valueUpdater } from '@/lib/utils'

import { Button } from '@/components/ui/button'
import { ArrowUpDown } from 'lucide-vue-next'

import { usePage, router } from "@inertiajs/vue3"
import { computed } from 'vue';

const sorting = ref<SortingState>([]);
interface User {
    id: string;
    name?: string;
    email?: string;
    stripe_id?: string;
    is_admin?: boolean;
    created_at?: string;
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
}

const props = defineProps<{
    filters: Filters;
    columns: Array<Column>;
}>();

const { props: pageProps } = usePage<{ users: User[] }>();

const filteredUsers = computed<User[]>(() => {
    let result = (pageProps.users || []) as User[];

    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(user =>
            user.name?.toLowerCase().includes(search) ||
            user.email?.toLowerCase().includes(search) ||
            user.stripe_id?.toLowerCase().includes(search)
        );
    }
    
    return result;
});
const tableColumns = computed<ColumnDef<User>[]>(() => {
    return props.columns
        .filter(col => col.active)
        .map(col => {
            const isSortable = ['id', 'has_granted_access', 'is_admin', 'created_at'].includes(col.column)

            return {
                accessorKey: col.column,
                header: ({ column }) => {
                    if (!isSortable) return h('span', col.header);
                    return h(Button, {
                        variant: 'ghost',
                        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    }, () => [col.header, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
                },
                accessorFn: row => {
                    if (col.column === 'is_admin') return row.is_admin ? 'Yes' : 'No'
                    if (col.column === 'has_granted_access') return row.has_granted_access ? 'Yes' : 'No'
                    if (col.column === 'created_at') return new Date(row.created_at).toLocaleDateString()
                    return row[col.column]
                },
                enableSorting: isSortable,
            }
        })
})

const table = useVueTable({
    get data() { return filteredUsers.value; },
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

const handleRowClick = (user: User) => {
    router.visit(route('admin.users.show', user.id));
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
                    <component 
                        v-if="!header.isPlaceholder" 
                        :is="typeof header.column.columnDef.header === 'function' 
                            ? header.column.columnDef.header({ column: header.column }) 
                            : header.column.columnDef.header"
                    />
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
                    No users found.
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