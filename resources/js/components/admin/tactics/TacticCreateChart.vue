<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    Card,
    CardContent,
} from '@/components/ui/card'

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
} from 'chart.js'

import { Line, Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
)

const { props } = usePage()
const tacticGraphData = props.tactic_graph_data

// parse months dynamically
const parseMonth = (monthString: string): Date => new Date(monthString)

// unique months across all users
const allMonths = Array.from(
    new Set(
        Object.values(tacticGraphData)
            .flatMap((userData: any) => Object.keys(userData))
    )
)

const labels = allMonths.sort(
    (a, b) => parseMonth(a).getTime() - parseMonth(b).getTime()
)

// fixed color palette so each user always has distinct + consistent color
const colorPalette = [
    '#4F46E5', '#58bc82', '#E63946', '#F4A261', '#2A9D8F',
    '#F59E0B', '#9D4EDD', '#06B6D4', '#EF4444', '#10B981'
]

const users = Object.keys(tacticGraphData)
const colorMap: Record<string, string> = {}
users.forEach((user, i) => {
    colorMap[user] = colorPalette[i % colorPalette.length]
})

const chartData = {
    labels,
    datasets: Object.entries(tacticGraphData).map(([user, data]: any) => ({
        label: user,
        data: labels.map(month => data[month] ?? 0),
        borderColor: colorMap[user],
        backgroundColor: colorMap[user] + '99', // slightly transparent
        tension: 0.3,
    }))
}

// responsive chart options
const chartOptions = computed(() => {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
            },
            title: {
                display: true,
                text: 'Stats Created per User per Month',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                },
            },
        },
    }

    // Remove scales for doughnut chart
    if (chartType.value === 'doughnut') {
        const { scales, ...optionsWithoutScales } = baseOptions
        return optionsWithoutScales
    }

    return baseOptions
})

// dynamic chart type
const chartType = ref<'line' | 'bar' | 'doughnut'>('line')
const chartComponent = computed(() => {
    switch (chartType.value) {
        case 'bar': return Bar
        case 'doughnut': return Doughnut
        default: return Line
    }
})

// Force chart resize on window resize
const chartRef = ref()
let resizeObserver: ResizeObserver | null = null

onMounted(() => {
    // Use ResizeObserver for better resize detection
    if (window.ResizeObserver && chartRef.value) {
        resizeObserver = new ResizeObserver(() => {
            if (chartRef.value?.chart) {
                chartRef.value.chart.resize()
            }
        })
        resizeObserver.observe(chartRef.value.$el)
    }
})

onUnmounted(() => {
    if (resizeObserver) {
        resizeObserver.disconnect()
    }
})
</script>

<template>
    <Card class="w-full min-h-[600px]"> <!-- Use min-height instead of fixed height -->
        <CardContent class="h-full flex flex-col space-y-4 p-4">
            <div class="flex gap-2 flex-wrap">
                <button
                    v-for="type in ['line','bar','doughnut']"
                    :key="type"
                    class="px-3 py-1 rounded-lg border text-sm capitalize transition"
                    :class="chartType === type ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                    @click="chartType = type"
                >
                    {{ type }}
                </button>
            </div>

            <!-- Chart container with proper sizing -->
            <div class="flex-1 relative min-h-[500px] w-full">
                <component
                    :is="chartComponent"
                    ref="chartRef"
                    :data="chartData"
                    :options="chartOptions"
                    class="absolute inset-0"
                />
            </div>
        </CardContent>
    </Card>
</template>