<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>New Subscriptions</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-2">
                <p class="text-2xl font-bold">{{ props.subscriptions.count }}</p>
                <p class="text-sm text-muted-foreground">
                    <span v-if="props.subscriptions.change > 0">+{{ props.subscriptions.change }}%</span>
                    <span v-else-if="props.subscriptions.change < 0">{{ props.subscriptions.change }}%</span>
                    <span v-else>{{ props.subscriptions.change }}%</span>
                    from last month
                </p>
            </div>
        </CardContent>
    </Card>
</template>
