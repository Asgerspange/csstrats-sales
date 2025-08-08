<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Monthly Revenue</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-2">
                <p class="text-2xl font-bold">${{ props.revenue.total | currency }}</p>
                <p class="text-sm text-muted-foreground">
                    <span v-if="props.revenue.change > 0">+{{ props.revenue.change }}%</span>
                    <span v-else-if="props.revenue.change < 0">{{ props.revenue.change }}%</span>
                    <span v-else>{{ props.revenue.change }}%</span>
                    from last month
                </p>
            </div>
        </CardContent>
    </Card>
</template>
