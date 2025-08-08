<script setup lang="ts">
    import { usePage } from '@inertiajs/vue3';
    const { props } = usePage();
    import {
        Card,
        CardContent,
    } from '@/components/ui/card';
    import {
        Chart as ChartJS,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
    } from 'chart.js';

    const chartData = {
        labels: props.monthlyRevenue.map((item: any) => item.month),
        datasets: [
            {
                label: 'Monthly Revenue',
                data: props.monthlyRevenue.map((item: any) => item.revenue),
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
            },
        ],
    }

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Monthly Revenue',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    };

    import { Line } from 'vue-chartjs';

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend
    );


</script>

<template>
    <Card>
        <CardContent class="h-full">
            <Line
                :data="chartData"
                :options="chartOptions"
                class="h-full w-full rounded-xl"
            />
        </CardContent>
    </Card>
</template>