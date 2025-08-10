<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ChevronDown } from 'lucide-vue-next';

import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

defineProps<{
    filters: {
        searchTerm: string
    },
    columns: Array<{
        id: string | number,
        column: string,
        header: string,
        active: boolean,
        direction: string
    }>
}>();

</script>

<template>
    <div class="flex items-center gap-8">
        <div class="flex item-center gap-4 w-full">
            <Input
                v-model="filters.searchTerm"
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
                        :key="column.id"
                        class="capitalize"
                        :model-value="column.active"
                        @update:model-value="(value: boolean) => {
                            column.active = !!value;
                        }"
                    >
                        {{ column.header }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
