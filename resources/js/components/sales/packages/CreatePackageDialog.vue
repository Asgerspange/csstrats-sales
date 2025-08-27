<script setup lang="ts">
import { ref, computed, toRaw } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { LoaderCircle } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/use-toast'
import Textarea from '@/components/ui/textarea/Textarea.vue';

const { toast } = useToast()

const customPackage = ref({
    name: '',
    price: '',
    monthly_price: '',
    description: '',
    max_teams: 0,
    max_members: 0,
    max_stratbooks: 0,
    features: [
        'All Tactics',
        computed(() => customPackage.value.max_teams + ' Team' + (customPackage.value.max_teams > 1 ? 's' : '')),
        computed(() => customPackage.value.max_members + ' User' + (customPackage.value.max_members > 1 ? 's' : '')),
        computed(() => customPackage.value.max_stratbooks + ' Stratbook' + (customPackage.value.max_stratbooks > 1 ? 's' : '')),
        'Priority Support',
    ],
    organisation_id: null,
});

const organisations = usePage().props.organisations || [];

const isCreatingPackage = ref(false);
const dialogOpen = ref(false);

const createPackage = async () => {
    isCreatingPackage.value = true;

    const formattedPackage = {
        ...toRaw(customPackage.value),
        features: [
            'All Tactics',
            customPackage.value.max_teams + ' Team' + (customPackage.value.max_teams > 1 ? 's' : ''),
            customPackage.value.max_members + ' User' + (customPackage.value.max_members > 1 ? 's' : ''),
            customPackage.value.max_stratbooks + ' Stratbook' + (customPackage.value.max_stratbooks > 1 ? 's' : ''),
            'Priority Support'
        ]
    };

    const response = await fetch('/packages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(formattedPackage),
    });
    try {

        if (response.status === 201) {
            const data = await response.json();
            toast({ title: 'Package Created', description: `${data.package.name} has been created successfully.`, variant: 'success' });
            customPackage.value = { name: '', price: '', monthly_price: '', description: '', max_teams: 0, max_members: 0, max_stratbooks: 0, features: [], organisation_id: null };
            dialogOpen.value = false;
            router.reload({ only: ['packages'] });
        } else if (response.status === 409) {
            toast({ title: 'Package Already Exists', description: `${formattedPackage.name} could not be created because the Package already exists.`, variant: 'destructive' });
        } else {
            const err = await response.json();
            toast({ title: 'Package Creation Failed', description: err?.message || 'Package could not be created.', variant: 'destructive' });
        }
    } catch (e) {
        toast({ title: 'Error', description: 'An unexpected error occurred.', variant: 'destructive' });
    } finally {
        isCreatingPackage.value = false;
    }
};

</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button variant="success">New Package</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>New Package</DialogTitle>
                <DialogDescription>
                    Fill in the package details below and click save.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="name">Name</Label>
                    <Input id="name" placeholder="Package Name" class="col-span-3" v-model="customPackage.name" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="price">Price</Label>
                    <Input id="price" placeholder="100" class="col-span-3" v-model="customPackage.price" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="monthly_price">Monthly Price</Label>
                    <Input id="monthly_price" placeholder="10" class="col-span-3" v-model="customPackage.monthly_price" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="description">Description</Label>
                    <Textarea id="description" placeholder="Package Description" class="col-span-3" v-model="customPackage.description" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_teams">Max Teams</Label>
                    <Input id="max_teams" type="number" class="col-span-3" v-model="customPackage.max_teams" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_members">Max Members</Label>
                    <Input id="max_members" type="number" class="col-span-3" v-model="customPackage.max_members" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_stratbooks">Max Stratbooks</Label>
                    <Input id="max_stratbooks" type="number" class="col-span-3" v-model="customPackage.max_stratbooks" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="organisation_id">Organisation</Label>
                    <Select v-model="customPackage.organisation_id" class="col-span-3">
                        <SelectTrigger class="w-fit">
                            <SelectValue placeholder="Select Organisation" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="org in organisations" :key="org.id" :value="org.id">
                                {{ org.name }} ({{ org.cvr }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="createPackage" :disabled="isCreatingPackage" variant="success">
                    <LoaderCircle v-if="isCreatingPackage" class="h-4 w-4 animate-spin" />
                    Create Package
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
