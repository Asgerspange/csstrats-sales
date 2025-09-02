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
const { unreleasedTactics, releasedTactics } = usePage().props
const tactics = ref([...unreleasedTactics, ...releasedTactics]);
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

interface Tactic {
    id: number,
    team_name?: string;
    team?: object;
    type?: string;
    description?: string;
    tags?: Array<string>;
    map?: string;
    side?: string;
    created_by?: string;
    is_released?: boolean;
    release_date?: string;
}

const tableColumns = computed<ColumnDef<Tactic>[]>(() => {
    return props.columns
        .filter(col => !['player_descriptions', 'player_timestamps', 'player_loadouts', 'matchdate', 'team'].includes(col.column))
        .map(col => {
            const isSortable = ['id', 'created_at', 'status', 'product_id'].includes(col.column)

            return {
                accessorKey: col.column,
                header: ({ column }) => {
                    if (col.header === 'Created_at') {
                        col.header = 'Created At'
                    }
                    if (col.header === 'Release_date') {
                        col.header = 'Release Date'
                    }
                    if (col.header === 'Is_released') {
                        col.header = 'Status'
                    }
                    if (col.header === 'Team_name') {
                        col.header = 'Team'
                    }
                    if (!isSortable) return h('span', col.header);

                    return h(Button, {
                        variant: 'ghost',
                        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
                    }, () => [col.header, h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
                },
                accessorFn: row => {
                    // For sorting purposes, return the raw values that can be properly sorted
                    // if (col.column === 'created') return row.created * 1000; // Convert to milliseconds for proper date sorting
                    
                    return row[col.column]
                },
                cell: ({ getValue, row }) => {
                    // For display purposes, format the values appropriately
                    const value = getValue();
                    if (col.column === 'is_released') {
                        return value ? 'Released' : 'Not Released';
                    }
                    if (col.column === 'created_at') {
                        return new Date(row.original.created_at).toLocaleDateString();
                    }
                    if (col.column === 'release_date') {
                        return new Date(row.original.release_date).toLocaleDateString();
                    }
                    if (col.column === 'matchlink') {
                        return h('a', { href: value, target: '_blank', class: 'text-blue-600 underline' }, 'View Match');
                    }
                    if (col.column === 'tags' && Array.isArray(value)) {
                        return value.join(', ');
                    }
                    // if (col.column === 'current_period_start') {
                    //     return new Date(row.original.current_period_start * 1000).toLocaleDateString() + ' - ' + new Date(row.original.current_period_end * 1000).toLocaleDateString();
                    // }
                    return value;
                },
                enableSorting: isSortable,
            }
        })
});

const filteredTactics = computed(() => {
    let result = (tactics.value || []) as Tactic[];
    console.log('Filtering tactics with', result);
    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(tac =>
            tac.team_name.toLowerCase().includes(search) ||
            tac.description.toLowerCase().includes(search) ||
            tac.tags.some(tag => tag.toLowerCase().includes(search))
        );
    }

    if (props.filters.releasedOnly) {
        result = result.filter(tac => tac.is_released === true);
    }

    if (props.filters.unreleasedOnly) {
        result = result.filter(tac => tac.is_released === false);
    }
    return result || [];
});
const table = useVueTable({
    get data() { return filteredTactics.value; },
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
                    No tactics found.
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