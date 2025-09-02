<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

const props = defineProps<{
    filters: {
        searchTerm: string,
        releasedOnly: boolean,
        unreleasedOnly: boolean,
    },
    columns: Array<{
        column: string,
        header: string,
        active: boolean,
        direction: string
    }>
}>();

const emit = defineEmits<{
    'update:filters': [filters: typeof props.filters]
    'update:columns': [columns: typeof props.columns]
}>();

const updateSearchTerm = (value: string) => {
    emit('update:filters', {
        ...props.filters,
        searchTerm: value
    });
};

const updateReleasedOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        releasedOnly: value
    });
};

const updateUnreleasedOnly = (value: boolean) => {
    emit('update:filters', {
        ...props.filters,
        unreleasedOnly: value
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
                    :model-value="filters.releasedOnly"
                    @update:model-value="updateReleasedOnly"
                    class="ml-4"
                />
                <label>Released only</label>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox 
                    :model-value="filters.unreleasedOnly"
                    @update:model-value="updateUnreleasedOnly"
                    class="ml-4"
                />
                <label>Unreleased only</label>
            </div>
        </div>
    </div>
</template>