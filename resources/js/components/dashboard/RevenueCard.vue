<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();

const totalRevenue = props.monthlyRevenue.flatMap((item) => item.revenue).reduce((acc, val) => acc + val, 0);
const totalRevenueLastMonth = props.monthlyRevenue.flatMap((item) => item.revenue).reduce((acc, val) => acc + val, 0) - props.revenue.total;
const totalRevenueChangeFromLastMonth = (totalRevenue - totalRevenueLastMonth) / totalRevenueLastMonth * 100;
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Total Yearly Revenue</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-2">
                <p class="text-2xl font-bold">{{ totalRevenue | currency }} DKK</p>
                <p class="text-sm text-muted-foreground">+{{ totalRevenueChangeFromLastMonth.toFixed(2) }}% from last month</p>
            </div>
        </CardContent>
    </Card>
</template>
