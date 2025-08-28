<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ChevronDown } from 'lucide-vue-next';
import { Checkbox } from '@/components/ui/checkbox';

import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

// Define props
const props = defineProps<{
    filters: {
        searchTerm: string,
        activeOnly: boolean,
        pastDueOnly: boolean,
        currencies: Array<{ currency: string, active: boolean }>
    },
    columns: Array<{
        column: string,
        header: string,
        active: boolean,
        direction: string
    }>
}>();

// Define emits for proper two-way binding
const emit = defineEmits<{
    'update:filters': [filters: typeof props.filters]
    'update:columns': [columns: typeof props.columns]
}>();

// Helper functions for updating data
const updateSearchTerm = (value: string) => {
    emit('update:filters', {
        ...props.filters,
        searchTerm: value
    });
};

const updateActiveOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        activeOnly: value
    });
};

const updatePastDueOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        pastDueOnly: value
    });
};

const updateColumnActive = (columnName: string, active: boolean) => {
    const updatedColumns = props.columns.map(col => 
        col.column === columnName ? { ...col, active } : col
    );
    emit('update:columns', updatedColumns);
};

const updateCurrencyActive = (currency: string, active: boolean) => {
    const updatedCurrencies = props.filters.currencies.map(curr => 
        curr.currency === currency ? { ...curr, active } : curr
    );
    emit('update:filters', {
        ...props.filters,
        currencies: updatedCurrencies
    });
};
</script>

<template>
    <div class="flex items-center gap-8">
        <div class="flex items-center gap-4 w-full">
            <Input
                :model-value="filters.searchTerm"
                @update:model-value="updateSearchTerm"
                type="text"
                placeholder="Search subscriptions (Customer [Email, Id, Name], Package)"
                class="input input-bordered w-full max-w-lg"
            />

            <div class="flex items-center gap-2">
                <Checkbox 
                    :model-value="filters.activeOnly"
                    @update:model-value="updateActiveOnly"
                    class="ml-4"
                />
                <label>Active only</label>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox 
                    :model-value="filters.pastDueOnly"
                    @update:model-value="updatePastDueOnly"
                    class="ml-4"
                />
                <label>Past due only</label>
            </div>
        </div>
    </div>
</template>