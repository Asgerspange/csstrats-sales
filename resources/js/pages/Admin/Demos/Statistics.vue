<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';
import { AlertTriangle, UploadCloud, Map, Percent, BarChart2 } from 'lucide-vue-next';

const { props } = usePage<{ demos: Array<{ id: number; name?: string; status: string; map?: string; created_at: string; error_message?: string }> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Demos', href: '/admin/demos' },
    { title: 'Statistics', href: '/admin/demos/statistics' },
];

const demos = computed(() => props.demos || []);

const failedDemos = computed(() =>
    demos.value.filter((demo) => demo.status === 'failed')
);

const uploadingDemos = computed(() =>
    demos.value.filter((demo) => ['uploading', 'processing'].includes(demo.status))
);

const demosByMap = computed(() => {
    return demos.value.reduce<Record<string, typeof demos.value>>((groups, demo) => {
        if (!demo.map) return groups;
        (groups[demo.map] ||= []).push(demo);
        return groups;
    }, {});
});

const mapPercentages = computed(() => {
    const total = demos.value.length;
    if (!total) return {};
    return Object.fromEntries(
        Object.entries(demosByMap.value).map(([map, list]) => [map, (list.length / total) * 100])
    );
});

const demosThisMonth = computed(() => {
    const now = new Date();
    return demos.value.filter(demo => {
        const createdAt = new Date(demo.created_at);
        return createdAt.getMonth() === now.getMonth() && createdAt.getFullYear() === now.getFullYear();
    });
});

const demosLastMonth = computed(() => {
    const now = new Date();
    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    return demos.value.filter(demo => {
        const createdAt = new Date(demo.created_at);
        return createdAt.getMonth() === lastMonth.getMonth() && createdAt.getFullYear() === lastMonth.getFullYear();
    });
});

const demoPercentChange = computed(() => {
    const thisMonthCount = demosThisMonth.value.length;
    const lastMonthCount = demosLastMonth.value.length;
    if (lastMonthCount === 0) return thisMonthCount === 0 ? 0 : 100;
    return ((thisMonthCount - lastMonthCount) / lastMonthCount) * 100;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-8">
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Failed Demos -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <AlertTriangle class="w-5 h-5 text-red-500" />
                        <h3 class="font-semibold text-gray-900">Failed Demos</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ failedDemos.length }}</p>
                    <p class="text-sm text-gray-500">Experiencing errors</p>
                </div>

                <!-- Uploading / Processing -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <UploadCloud class="w-5 h-5 text-blue-500" />
                        <h3 class="font-semibold text-gray-900">Uploading / Processing</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ uploadingDemos.length }}</p>
                    <p class="text-sm text-gray-500">Currently being handled</p>
                </div>

                <!-- Maps Detected -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <Map class="w-5 h-5 text-green-500" />
                        <h3 class="font-semibold text-gray-900">Maps Detected</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ Object.keys(demosByMap).length }}</p>
                    <p class="text-sm text-gray-500">Grouped maps</p>
                </div>

                <!-- Total Demos -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <Percent class="w-5 h-5 text-purple-500" />
                        <h3 class="font-semibold text-gray-900">Total Demos</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ demos.length }}</p>
                    <p class="text-sm text-gray-500">Overall count</p>
                </div>
            </div>

            <!-- Month / Year / Percent Change Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- This Month -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <BarChart2 class="w-5 h-5 text-indigo-500" />
                        <h3 class="font-semibold text-gray-900">This Month</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ demosThisMonth.length }}</p>
                    <p class="text-sm text-gray-500">Demos created this month</p>
                </div>

                <!-- Last Month -->
                <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3 mb-2">
                        <BarChart2 class="w-5 h-5 text-gray-500" />
                        <h3 class="font-semibold text-gray-900">Last Month</h3>
                    </div>
                    <p class="text-3xl font-bold text-gray-800">{{ demosLastMonth.length }}</p>
                    <p class="text-sm text-gray-500">Compared to this month</p>
                </div>

                <!-- Percent Change -->
                <div
                    class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition"
                    :class="demoPercentChange > 0 ? 'border-green-300 bg-green-50' : demoPercentChange < 0 ? 'border-red-300 bg-red-50' : ''"
                >
                    <div class="flex items-center gap-3 mb-2">
                        <Percent
                            class="w-5 h-5"
                            :class="demoPercentChange > 0 ? 'text-green-600' : demoPercentChange < 0 ? 'text-red-600' : 'text-gray-500'"
                        />
                        <h3 class="font-semibold text-gray-900">Percent Change</h3>
                    </div>
                    <p
                        class="text-3xl font-bold"
                        :class="demoPercentChange > 0 ? 'text-green-700' : demoPercentChange < 0 ? 'text-red-700' : 'text-gray-800'"
                    >
                        {{ demoPercentChange.toFixed(1) }}%
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ demoPercentChange > 0 ? 'Increase' : demoPercentChange < 0 ? 'Decrease' : 'No change' }} from last month
                    </p>
                </div>
            </div>

            <!-- Existing Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Failed Demos -->
                <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <AlertTriangle class="w-5 h-5 text-red-500" />
                        Failed Demos
                    </h2>
                    <div v-if="failedDemos.length" class="max-h-64 overflow-y-auto divide-y divide-gray-100">
                        <div v-for="demo in failedDemos" :key="demo.id" class="py-2 text-sm text-gray-700">
                            <span class="font-medium text-red-600">{{ demo.name || 'Unnamed Demo' }}</span>
                            <span class="text-gray-500"> (ID: {{ demo.id }})</span>
                            <div class="text-xs text-gray-500">Error: {{ demo.error_message || 'Unknown error' }}</div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500">No failed demos.</p>
                </div>

                <!-- Uploading Demos -->
                <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <UploadCloud class="w-5 h-5 text-blue-500" />
                        Uploading / Processing
                    </h2>
                    <div v-if="uploadingDemos.length" class="max-h-64 overflow-y-auto divide-y divide-gray-100">
                        <div v-for="demo in uploadingDemos" :key="demo.id" class="py-2 text-sm text-gray-700">
                            <span class="font-medium text-blue-600">{{ demo.name || 'Unnamed Demo' }}</span>
                            <span class="text-gray-500"> (ID: {{ demo.id }})</span>
                            <div class="text-xs text-gray-500">Status: {{ demo.status }}</div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500">No demos currently uploading or processing.</p>
                </div>
            </div>

            <!-- Demos by Map -->
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <Map class="w-5 h-5 text-green-500" />
                    Demos by Map
                </h2>
                <div v-if="Object.keys(demosByMap).length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(demos, map) in demosByMap"
                        :key="map"
                        class="p-4 border border-gray-100 rounded-lg bg-gray-50 hover:bg-gray-100 transition"
                    >
                        <h3 class="font-semibold text-green-700 mb-2">{{ map }}</h3>
                        <ul class="space-y-1 text-sm text-gray-700 max-h-32 overflow-y-auto">
                            <li v-for="demo in demos" :key="demo.id">
                                • {{ demo.name || 'Unnamed Demo' }} (ID: {{ demo.id }})
                            </li>
                        </ul>
                    </div>
                </div>
                <p v-else class="text-gray-500">No demos grouped by map.</p>
            </div>

            <!-- Map Percentages -->
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <Percent class="w-5 h-5 text-purple-500" />
                    Map Percentages
                </h2>
                <div v-if="Object.keys(mapPercentages).length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="(percentage, map) in mapPercentages"
                        :key="map"
                        class="p-4 border border-gray-100 rounded-lg bg-purple-50 hover:bg-purple-100 transition"
                    >
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-purple-700">{{ map }}</span>
                            <span class="text-gray-700">{{ percentage.toFixed(2) }}%</span>
                        </div>
                        <div class="mt-2 w-full bg-purple-200 rounded-full h-2">
                            <div
                                class="bg-purple-600 h-2 rounded-full"
                                :style="{ width: `${percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-500">No map percentages available.</p>
            </div>
        </div>
    </AppLayout>
</template>
