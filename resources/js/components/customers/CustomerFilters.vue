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
        subscribedOnly: boolean,
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

const updateSubscribedOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        subscribedOnly: value
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
                placeholder="Search customers (Name, Email, Stripe ID)"
                class="input input-bordered w-full max-w-xs"
            />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline">
                        Columns <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuCheckboxItem 
                        v-for="column in columns"
                        :key="column.column"
                        class="capitalize"
                        :model-value="column.active"
                        @update:model-value="(value: boolean) => updateColumnActive(column.column, value)"
                    >
                        {{ column.header }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline">
                        Currencies <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuCheckboxItem 
                        v-for="currency in filters.currencies"
                        :key="currency.currency"
                        class="capitalize"
                        :model-value="currency.active"
                        @update:model-value="(value: boolean) => updateCurrencyActive(currency.currency, value)"
                    >
                        {{ currency.currency }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <div class="flex items-center gap-2">
                <Checkbox 
                    :model-value="filters.subscribedOnly"
                    @update:model-value="updateSubscribedOnly"
                    class="ml-4"
                />
                <label>Subscribed only</label>
            </div>
        </div>
    </div>
</template>