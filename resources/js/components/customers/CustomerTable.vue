<script setup lang="ts">
    import {
        Table,
        TableBody,
        TableCell,
        TableHead,
        TableHeader,
        TableRow,
    } from "@/components/ui/table"
    
    import { usePage } from "@inertiajs/vue3"

    import { watch, ref } from 'vue';

    const parentProps = defineProps<{
        filters: {
            searchTerm: string
        },
        columns: Array<{
            column: string,
            header: string,
            active: boolean,
            direction: string
        }>
    }>()

    const { props } = usePage();

    const customers = ref(props.customers || []);
    const columns = ref(parentProps.columns);

    const convertStripeTimestamp = (timestamp: number): string => {
        const ts = timestamp.toString().length > 10 
            ? Math.floor(timestamp / 1000) 
            : timestamp;

        if (isNaN(ts) || ts < 0 || ts > 2147483647) {
            return 'Invalid timestamp';
        }

        const date = new Date(ts * 1000);

        return date.toLocaleString('en-US', {
            timeZone: 'UTC',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour12: false
        });
    }

    watch(() => parentProps.filters.searchTerm, (newSearchTerm) => {
        customers.value = props.customers.filter(customer =>
            customer.name?.toLowerCase().includes(newSearchTerm.toLowerCase()) ||
            customer.email?.toLowerCase().includes(newSearchTerm.toLowerCase()) ||
            customer.cus_id?.toLowerCase().includes(newSearchTerm.toLowerCase())
        );
    });
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <template v-for="column in columns" :key="column.column">
                    <TableHead
                        v-if="column.active"
                    >
                        {{ column.header }}
                    </TableHead>
                </template>
            </TableRow>
        </TableHeader>
        <TableBody>
            <template v-for="customer in customers" :key="customer.id">
                <TableRow>
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