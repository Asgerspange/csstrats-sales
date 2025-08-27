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
    FlexRender,
    type ColumnDef,
} from '@tanstack/vue-table';

import { usePage, router } from "@inertiajs/vue3"
import { computed, h } from 'vue';

interface Payment {
    amount?: number;
    currency?: string;
    status?: string;
    customer_id: string;
    subscription_id: string;
    date?: string;
    [key: string]: any;
}

const excludedKeys = ['customer_id'];

const { props: pageProps } = usePage<{ payments: Payment[] }>();

const filteredPayments = computed<Payment[]>(() => {
    let result = (pageProps.payments || []) as Payment[];

    // if (props.filters.searchTerm) {
    //     const search = props.filters.searchTerm.toLowerCase();
    //     result = result.filter(payment =>
    //         payment.customer_id?.toLowerCase().includes(search) ||
    //         payment.subscription_id?.toLowerCase().includes(search) ||
    //         payment.amount?.toString().includes(search) ||
    //         payment.currency?.toLowerCase().includes(search) ||
    //         payment.date?.toLowerCase().includes(search)
    //     );
    // }

    // const activeCurrencies = props.filters.currencies
    //     .filter(c => c.active)
    //     .map(c => c.currency);
    
    // if (activeCurrencies.length > 0 && activeCurrencies.length < props.filters.currencies.length) {
    //     result = result.filter(customer => 
    //         activeCurrencies.includes(customer.currency)
    //     );
    // }
    
    return result;
});

const tableColumns = computed(() => {
    return Object.keys(pageProps.payments_this_month[0] || {})
        .filter(key => !excludedKeys.includes(key))
        .filter(key => {
            const value = pageProps.payments_this_month[0]?.[key];
            return !(Array.isArray(value) && value.length === 0);
        })
        .map(key => {
            if (key === 'billing_reason') {
                return {
                    header: 'Reason',
                    accessorKey: key,
                    cell: (info: any) => {
                        const val = info.getValue();
                        // if (val === 'subscription_cycle') {
                        //     return h(TooltipProvider, { delayDuration: 100 }, () =>
                        //         h(Tooltip, null, {
                        //             default: () => [
                        //                 h(TooltipTrigger, { asChild: true }, () =>
                        //                     h(CalendarSync, { class: 'w-4 h-4 inline-block text-gray-600' })
                        //                 ),
                        //                 h(TooltipContent, null, () => String(val))
                        //             ]
                        //         })
                        //     );
                        // }

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

            if (key === 'customer') {
                return {
                    header: 'Customer',
                    accessorKey: key,
                    cell: (info: any) => {
                        const val = info.getValue();
                        return h('a', { href: route('sales.customers.show', val.id), class: 'text-blue-600 hover:underline' }, val.name);
                    },
                };
            }

            // if (key === 'subscription_id') {
            //     return {
            //         header: 'Subscription ID',
            //         accessorKey: key,
            //         cell: (info: any) => {
            //             const val = info.getValue();
            //             return h('a', { href: `/subscriptions/${val}`, class: 'text-blue-600 hover:underline' }, 'View Subscription');
            //         },
            //     };
            // }

            if (key == 'amount') {
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


            return {
                header: key.charAt(0).toUpperCase() + key.slice(1),
                accessorKey: key,
            };
        }) as ColumnDef<any>[];
});

const table = useVueTable({
    get data() { return filteredPayments.value; },
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
            id: 'date', // make sure your column accessorKey is "created"
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