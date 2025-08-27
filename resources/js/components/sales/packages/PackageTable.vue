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

interface Package {
    id: number;
    name?: string;
    organisation?: object;
    price?: string;
    monthly_price?: string;
    max_teams: number;
    max_members: number;
    max_stratbooks: number;
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
}

const props = defineProps<{
    filters: Filters;
    columns: Array<Column>;
}>();

const page = usePage();
const packages = computed(() => page.props.packages || []);
const filteredPackages = computed(() => {
    let result = (packages.value || []) as Package[];

    if (props.filters.searchTerm) {
        const search = props.filters.searchTerm.toLowerCase();
        result = result.filter(p =>
            p.name?.toLowerCase().includes(search) ||
            p.organisation?.cvr?.toLowerCase().includes(search)
            || p.organisation?.name?.toLowerCase().includes(search)
        );
    }

    return result;
});

const tableColumns = computed<ColumnDef<Package>[]>(() => {
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
    get data() { return filteredPackages.value; },
    get columns() { return tableColumns.value; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
        pagination: {
            pageSize: 10,
        },
    },
});

const deletingPackage = ref<number | null>(null);

const handleRowClick = (p: Package) => {
    router.visit(route('packages.show', p.id));
};

const deletePackage = (id: number) => {
    deletingPackage.value = id;
    fetch(`/packages/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    }).then(response => {
        if (response.ok) {
            toast({
                title: 'Package Deleted',
                description: 'The package has been deleted successfully.',
                variant: 'success'
            });

            router.reload({ only: ['packages'] });
        } else {
            toast({
                title: 'Failed to Delete Package',
                description: 'The package could not be deleted.',
                variant: 'destructive'
            });
            console.error('Failed to delete organisation');
        }
    });
}

const copyLink = (id: number, region: string) => {
    const link = region === 'us' 
        ? `https://us.csstrats.org/custom-package/${id}` 
        : `https://csstrats.dk/custom-package/${id}`;
    navigator.clipboard.writeText(link).then(() => {
        toast({
            title: 'Link Copied',
            description: 'The package link has been copied to your clipboard.',
            variant: 'success'
        });
    }).catch(err => {
        console.error('Failed to copy link:', err);
        toast({
            title: 'Failed to Copy Link',
            description: 'There was an error copying the link.',
            variant: 'destructive'
        });
    });
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
                    <TableCell>
                        <Button size="sm" variant="success"
                            @click.stop="copyLink(row.original.id)" :disabled="deletingPackage === row.original.id"
                        >
                            <LoaderCircle v-if="deletingPackage === row.original.id" class="h-4 w-4 animate-spin" />
                            Copy Link
                        </Button>
                    </TableCell>
                    <TableCell>
                        <Button size="sm" variant="success"
                            @click.stop="copyLink(row.original.id, 'us')" :disabled="deletingPackage === row.original.id"
                        >
                            <LoaderCircle v-if="deletingPackage === row.original.id" class="h-4 w-4 animate-spin" />
                            Copy US Link
                        </Button>
                    </TableCell>
                    <!-- <TableCell>
                        <Button size="sm" variant="destructive"
                            @click.stop="deletePackage(row.original.id)" :disabled="deletingPackage === row.original.id"
                        >
                            <LoaderCircle v-if="deletingPackage === row.original.id" class="h-4 w-4 animate-spin" />
                            Delete
                        </Button>
                    </TableCell> -->
                </TableRow>
            </template>
            <TableRow v-else>
                <TableCell :colspan="tableColumns.length" class="h-24 text-center">
                    No packages found.
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