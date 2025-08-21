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
            FlexRender,
            getSortedRowModel
        } from '@tanstack/vue-table';
        import { CalendarSync, CalendarPlus } from "lucide-vue-next";
        import { computed, h } from 'vue';

        const props = defineProps<{
            invoices: any[];
        }>();

        const excludedKeys = ['id', 'invoice_id', 'customer', 'collection_method', 'data'];
        import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip"

        const tableColumns = computed(() => {
            return Object.keys(props.invoices[0] || {})
                .filter(key => !excludedKeys.includes(key))
                .filter(key => {
                    const value = props.invoices[0]?.[key];
                    return !(Array.isArray(value) && value.length === 0);
                })
                .map(key => {
                    if (key === 'billing_reason') {
                        return {
                            header: 'Reason',
                            accessorKey: key,
                            cell: (info: any) => {
                                const val = info.getValue();
                                if (val === 'subscription_cycle') {
                                    return h(TooltipProvider, { delayDuration: 100 }, () =>
                                        h(Tooltip, null, {
                                            default: () => [
                                                h(TooltipTrigger, { asChild: true }, () =>
                                                    h(CalendarSync, { class: 'w-4 h-4 inline-block text-gray-600' })
                                                ),
                                                h(TooltipContent, null, () => String(val))
                                            ]
                                        })
                                    );
                                }

                                if (val === 'subscription_create') {
                                    return h(TooltipProvider, { delayDuration: 100 }, () =>
                                        h(Tooltip, null, {
                                            default: () => [
                                                h(TooltipTrigger, { asChild: true }, () =>
                                                    h(CalendarPlus, { class: 'w-4 h-4 inline-block text-gray-600' })
                                                ),
                                                h(TooltipContent, null, () => String(val))
                                            ]
                                        })
                                    );
                                }
                                return String(val ?? '');
                            },
                        };
                    }

                    if (key === 'invoice_pdf') {
                        return {
                            header: 'PDF',
                            accessorKey: key,
                            cell: (info: any) => {
                                const val = info.getValue();
                                return h('a', { href: val, target: '_blank', download: true, class: 'text-blue-600 hover:underline' }, 'View PDF');
                            },
                        };
                    }

                    if (key === 'sub_id') {
                        return {
                            header: 'Subscription ID',
                            accessorKey: key,
                            cell: (info: any) => {
                                const val = info.getValue();
                                return h('a', { href: `/subscriptions/${val}`, class: 'text-blue-600 hover:underline' }, 'View Subscription');
                            },
                        };
                    }

                    if (key == 'subtotal' || key == 'subtotal_excluding_tax' || key == 'amount_paid') {
                        return {
                            header: key.charAt(0).toUpperCase() + key.slice(1),
                            accessorKey: key,
                            cell: (info: any) => {
                                const val = info.getValue();
                                return new Intl.NumberFormat('en-US', {
                                    style: 'currency',
                                    currency: info.row.original.currency || 'USD',
                                }).format(val / 100);
                            },
                        };
                    }

                    if (key === 'status_transitions') {
                        return {
                            header: 'Status',
                            accessorKey: key,
                            cell: (info: any) => {
                                const transitions = info.getValue();
                                if (!transitions) return 'N/A';

                                const status = transitions.finalized_at ? 'Finalized' : 'Pending';
                                const date = transitions.finalized_at ? new Date(transitions.finalized_at).toLocaleDateString() : 'N/A';
                                return `${status} on ${date}`;
                            },
                        };
                    }

                    if (key === 'created') {
                        return {
                            header: 'Created',
                            accessorKey: key,
                            cell: (info: any) => {
                                const val = info.getValue();
                                return new Date(val).toLocaleDateString();
                            },
                        };
                    }

                    return {
                        header: key.charAt(0).toUpperCase() + key.slice(1),
                        accessorKey: key,
                    };
                }) as ColumnDef<any>[];
        });

        const filteredInvoices = computed(() => {
            return props.invoices || [];
        });
        const table = useVueTable({
            get data() { return filteredInvoices.value; },
            get columns() { return tableColumns.value; },
            getCoreRowModel: getCoreRowModel(),
            getPaginationRowModel: getPaginationRowModel(),
            getSortedRowModel: getSortedRowModel(),
            initialState: {
                pagination: {
                    pageSize: 10,
                },
                sorting: [
                {
                    id: 'created', // make sure your column accessorKey is "created"
                    desc: true, // true = newest first
                },
            ],
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
                    {{ header.isPlaceholder ? '' : header.column.columnDef.header }}
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
                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
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