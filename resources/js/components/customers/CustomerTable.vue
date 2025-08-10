<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"

import { usePage, router } from "@inertiajs/vue3"
import { computed } from 'vue';

const props = defineProps<{
    filters: {
        searchTerm: string,
        subscribedOnly: boolean,
        currencies: Array<{ currency: string, active: boolean }>
    },
    columns: Array<{
        column: string,
        header: string,
        active: boolean,
        direction: string
    }>
}>();

const { props: pageProps } = usePage();

const filteredCustomers = computed(() => {
    let result = pageProps.customers || [];
    
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

const timestampCache = new Map();
const convertStripeTimestamp = (timestamp: number): string => {
    if (timestampCache.has(timestamp)) {
        return timestampCache.get(timestamp);
    }
    
    const ts = timestamp.toString().length > 10 
        ? Math.floor(timestamp / 1000) 
        : timestamp;

    if (isNaN(ts) || ts < 0 || ts > 2147483647) {
        return 'Invalid timestamp';
    }

    const date = new Date(ts * 1000);
    const result = date.toLocaleString('en-US', {
        timeZone: 'UTC',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour12: false
    });
    
    timestampCache.set(timestamp, result);
    return result;
};
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <template v-for="column in columns" :key="column.column">
                    <TableHead v-if="column.active">
                        {{ column.header }}
                    </TableHead>
                </template>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-for="customer in filteredCustomers" :key="customer.id">
                <TableRow @click="router.visit(route('customers.show', customer.id))" class="cursor-pointer hover:bg-gray-100">
                    <template v-for="column in columns" :key="column.column">
                        <TableCell v-if="column.active">
                            <span v-if="column.column === 'created'">
                                {{ convertStripeTimestamp(customer[column.column]) }}
                            </span>
                            <span v-else-if="column.column === 'subscribed'">
                                {{ customer[column.column] ? 'Yes' : 'No' }}
                            </span>
                            <span v-else>
                                {{ customer[column.column] }}
                            </span>
                        </TableCell>
                    </template>
                </TableRow>
            </template>
        </TableBody>
    </Table>
</template>