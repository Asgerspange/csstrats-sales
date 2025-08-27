<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();

const unpaidPayments = props.payments_this_month.filter(payment => payment.status === 'unpaid');
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Unpaid / Failed Payments</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="flex flex-col gap-2">
                <p class="text-2xl font-bold">{{unpaidPayments.length }} </p>
            </div>
        </CardContent>
    </Card>
</template>
