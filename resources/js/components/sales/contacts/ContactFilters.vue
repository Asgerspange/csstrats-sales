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
        danishOnly: boolean,
        countries: Array<{ country: string, active: boolean }>
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

const updateDanishOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        danishOnly: value
    });
};

const updateColumnActive = (columnName: string, active: boolean) => {
    const updatedColumns = props.columns.map(col => 
        col.column === columnName ? { ...col, active } : col
    );
    emit('update:columns', updatedColumns);
};

const updateCountryActive = (country: string, active: boolean) => {
    const updatedCountries = props.filters.countries.map(curr => 
        curr.country === country ? { ...curr, active } : curr
    );
    emit('update:filters', {
        ...props.filters,
        countries: updatedCountries
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
                placeholder="Search Contacts (Name, Email, Phone, Organisation)"
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
                        Countries <ChevronDown class="ml-2 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuCheckboxItem 
                        v-for="country in filters.countries"
                        :key="country.country"
                        class="capitalize"
                        :model-value="country.active"
                        @update:model-value="(value: boolean) => updateCountryActive(country.country, value)"
                    >
                        {{ country.country }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <div class="flex items-center gap-2">
                <Checkbox 
                    :model-value="filters.danishOnly"
                    @update:model-value="updateDanishOnly"
                    class="ml-4"
                />
                <label>Danish only</label>
            </div>
        </div>
    </div>
</template>