<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Monthly Sales</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-2">
                <p class="text-2xl font-bold">{{ props.sales.count }}</p>
                <p class="text-sm text-muted-foreground">
                    <span v-if="props.sales.change > 0">+{{ props.sales.change }}%</span>
                    <span v-else-if="props.sales.change < 0">{{ props.sales.change }}%</span>
                    <span v-else>{{ props.sales.change }}%</span>
                    from last month
                </p>
            </div>
        </CardContent>
    </Card>
</template>
