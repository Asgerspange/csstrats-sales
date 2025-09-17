<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

interface Tactic {
    id: number
    type: string
    side: 'CT' | 'T'
    team_name: string
    map: string
    tags: string[]
}

interface Props {
    tactics?: Tactic[]
}

const props = defineProps<Props>()

// Get tactics from props or page props
const { props: pageProps } = usePage()
const tactics = computed(() => props.tactics || pageProps.tactics || [])

// Sorting and filtering
const sortBy = ref<'total' | 'ct' | 't' | 'name'>('total')
const filterSide = ref<'all' | 'CT' | 'T'>('all')

// Group tactics by type and side
const tacticBreakdown = computed(() => {
    const breakdown: Record<string, { CT: number, T: number, total: number }> = {}
    
    const filteredTactics = filterSide.value === 'all' 
        ? tactics.value 
        : tactics.value.filter((tactic: Tactic) => tactic.side === filterSide.value)
    
    filteredTactics.forEach((tactic: Tactic) => {
        const type = tactic.type || 'Unknown'
        
        if (!breakdown[type]) {
            breakdown[type] = { CT: 0, T: 0, total: 0 }
        }
        
        breakdown[type][tactic.side]++
        breakdown[type].total++
    })
    
    // Sort by selected criteria
    return Object.entries(breakdown)
        .sort(([a, countsA], [b, countsB]) => {
            switch (sortBy.value) {
                case 'ct': return countsB.CT - countsA.CT
                case 't': return countsB.T - countsA.T
                case 'name': return a.localeCompare(b)
                default: return countsB.total - countsA.total
            }
        })
        .map(([type, counts]) => ({ type, ...counts }))
})

// Total counts
const totalCounts = computed(() => {
    const filteredTactics = filterSide.value === 'all' 
        ? tactics.value 
        : tactics.value.filter((tactic: Tactic) => tactic.side === filterSide.value)
        
    return filteredTactics.reduce((acc, tactic: Tactic) => {
        acc.total++
        acc[tactic.side]++
        return acc
    }, { CT: 0, T: 0, total: 0 })
})

// Get the most popular tactic type
const mostPopularType = computed(() => {
    return tacticBreakdown.value[0]?.type || 'None'
})

// Calculate side balance (closer to 50% = more balanced)
const sideBalance = computed(() => {
    if (totalCounts.value.total === 0) return 0
    const ctPercent = (totalCounts.value.CT / totalCounts.value.total) * 100
    return Math.abs(50 - ctPercent)
})

const getBalanceColor = (balance: number) => {
    if (balance < 10) return 'text-green-600'
    if (balance < 25) return 'text-yellow-600'
    return 'text-red-600'
}

const getBalanceText = (balance: number) => {
    if (balance < 10) return 'Well Balanced'
    if (balance < 25) return 'Moderately Balanced'
    return 'Imbalanced'
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header with Controls -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-4 border-b">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tactical Overview</h1>
                <p class="text-sm text-gray-600 mt-1">Analyze your team's tactical distribution and balance</p>
            </div>
            
            <!-- Filters and Sorting -->
            <div class="flex flex-col sm:flex-row gap-2">
                <select 
                    v-model="filterSide" 
                    class="px-2 py-1.5 border border-gray-300 rounded-md bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="all">All Sides</option>
                    <option value="CT">CT Only</option>
                    <option value="T">T Only</option>
                </select>
                
                <select 
                    v-model="sortBy" 
                    class="px-2 py-1.5 border border-gray-300 rounded-md bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="total">Sort by Total</option>
                    <option value="ct">Sort by CT</option>
                    <option value="t">Sort by T</option>
                    <option value="name">Sort by Name</option>
                </select>
            </div>
        </div>

        <!-- Key Metrics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Tactics -->
            <Card class="bg-gradient-to-br from-slate-50 to-slate-100 border-slate-200">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-slate-600">Total Tactics</p>
                            <p class="text-2xl font-bold text-slate-900">{{ totalCounts.total }}</p>
                        </div>
                        <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- CT Side -->
            <Card class="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-blue-700">CT Tactics</p>
                            <p class="text-xl font-bold text-blue-900">{{ totalCounts.CT }}</p>
                            <p class="text-xs text-blue-600 mt-1">
                                {{ totalCounts.total > 0 ? ((totalCounts.CT / totalCounts.total) * 100).toFixed(1) : 0 }}% of total
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- T Side -->
            <Card class="bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-orange-700">T Tactics</p>
                            <p class="text-2xl font-bold text-orange-900">{{ totalCounts.T }}</p>
                            <p class="text-xs text-orange-600 mt-1">
                                {{ totalCounts.total > 0 ? ((totalCounts.T / totalCounts.total) * 100).toFixed(1) : 0 }}% of total
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-orange-200 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Balance Metric -->
            <Card class="bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200">
                <CardContent class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Side Balance</p>
                            <p class="text-base font-bold" :class="getBalanceColor(sideBalance)">
                                {{ getBalanceText(sideBalance) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Most popular: {{ mostPopularType }}
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                            </svg>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Detailed Breakdown -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <CardTitle class="text-lg font-bold">Tactical Breakdown</CardTitle>
                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                        <span class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-blue-500 rounded-full mr-1.5"></div>
                            Counter-Terrorist
                        </span>
                        <span class="flex items-center">
                            <div class="w-2.5 h-2.5 bg-orange-500 rounded-full mr-1.5"></div>
                            Terrorist
                        </span>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div 
                        v-for="(item, index) in tacticBreakdown" 
                        :key="item.type"
                        class="group border border-gray-100 rounded-lg p-2 hover:border-gray-200 hover:shadow transition-all duration-200"
                        :class="{
                            'border-yellow-200 bg-yellow-50': index === 0 && tacticBreakdown.length > 1
                        }"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-2">
                                <div v-if="index === 0 && tacticBreakdown.length > 1" 
                                     class="flex items-center justify-center w-5 h-5 bg-yellow-400 text-yellow-900 rounded-full text-[10px] font-bold">
                                    #1
                                </div>
                                <h3 class="font-bold text-base text-gray-900">{{ item.type }}</h3>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900">{{ item.total }}</div>
                                <div class="text-xs text-gray-500">total tactics</div>
                            </div>
                        </div>
                        
                        <!-- Visual Distribution Bar -->
                        <div class="mb-3">
                            <div class="flex h-5 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                                <div 
                                    v-if="item.CT > 0"
                                    class="bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold transition-all duration-300 hover:from-blue-500 hover:to-blue-700"
                                    :style="{ width: `${(item.CT / item.total) * 100}%` }"
                                >
                                    {{ item.CT }}
                                </div>
                                <div 
                                    v-if="item.T > 0"
                                    class="bg-gradient-to-r from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-bold transition-all duration-300 hover:from-orange-500 hover:to-orange-700"
                                    :style="{ width: `${(item.T / item.total) * 100}%` }"
                                >
                                    {{ item.T }}
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Stats Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <!-- CT Stats -->
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-3 transition-all duration-200 hover:bg-blue-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-1.5">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <span class="text-sm font-semibold text-blue-800">Counter-Terrorist</span>
                                    </div>
                                    <span class="text-lg font-bold text-blue-900">{{ item.CT }}</span>
                                </div>
                                <div class="mt-1.5 flex justify-between items-center">
                                    <span class="text-xs text-blue-700">
                                        {{ ((item.CT / item.total) * 100).toFixed(1) }}% of type
                                    </span>
                                    <span class="text-xs text-blue-600">
                                        {{ totalCounts.CT > 0 ? ((item.CT / totalCounts.CT) * 100).toFixed(1) : 0 }}% of all CT
                                    </span>
                                </div>
                            </div>

                            <!-- T Stats -->
                            <div class="bg-orange-50 border border-orange-200 rounded-md p-3 transition-all duration-200 hover:bg-orange-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-1.5">
                                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                        <span class="text-sm font-semibold text-orange-800">Terrorist</span>
                                    </div>
                                    <span class="text-lg font-bold text-orange-900">{{ item.T }}</span>
                                </div>
                                <div class="mt-1.5 flex justify-between items-center">
                                    <span class="text-xs text-orange-700">
                                        {{ ((item.T / item.total) * 100).toFixed(1) }}% of type
                                    </span>
                                    <span class="text-xs text-orange-600">
                                        {{ totalCounts.T > 0 ? ((item.T / totalCounts.T) * 100).toFixed(1) : 0 }}% of all T
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="tacticBreakdown.length === 0" class="text-center py-8">
                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h3 class="text-base font-medium text-gray-900 mb-1">No tactics found</h3>
                    <p class="text-sm text-gray-600">Start adding tactics to see detailed breakdowns and analytics.</p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
