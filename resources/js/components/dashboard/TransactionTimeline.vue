<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import { Card, CardContent, CardTitle } from '@/components/ui/card';
const { props } = usePage();

console.log(props.recentSales);
</script>

<template>
    <Card class="h-full">
        <CardContent class="p-4">
            <CardTitle>Recent Transactions</CardTitle>
            <ul class="space-y-2">
                <li v-for="transaction in props.recentSales" :key="transaction.id" class="flex justify-between items-center">
                    <span class="font-medium">{{ transaction.customer }}</span>
                    <span>{{ transaction.description }}</span>
                    <span class="text-gray-500">{{ transaction.amount }} - {{ transaction.currency }}</span>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
