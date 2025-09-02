<script setup lang="ts">
import { ref } from 'vue';
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
import { router } from '@inertiajs/vue3';

import { useToast } from '@/components/ui/toast/use-toast'
const { toast } = useToast()
const organisation = ref({
    name: '',
    cvr: '',
    country: '',
    address: '',
    zip: '',
    type: ''
})

const isCreatingOrganisation = ref(false);
const dialogOpen = ref(false);

const createOrganisation = async () => {
    isCreatingOrganisation.value = true;

    const formattedOrganisation = {
        ...organisation.value,
        country: organisation.value.country.toUpperCase(),
    };

    try {
        const response = await fetch('/sales/organisations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(formattedOrganisation),
        });

        if (response.status === 201) {
            const data = await response.json();

            toast({
                title: 'Organisation Created',
                description: `${data.organisation.name} has been created successfully.`,
                variant: 'success'
            });

            organisation.value = { name: '', cvr: '', country: '', address: '', type: '', zip: '' };
            dialogOpen.value = false;

            router.reload({ only: ['organisations'] });
        } else if (response.status === 409) {
            toast({
                title: 'CVR Already Exists',
                description: `${formattedOrganisation.name} could not be created because the CVR already exists.`,
                variant: 'destructive'
            });

        } else {
            const err = await response.json();
            toast({
                title: 'Organisation Creation Failed',
                description: err?.message || 'Organisation could not be created.',
                variant: 'destructive'
            });
        }
    } catch (e) {
        toast({
            title: 'Error',
            description: 'An unexpected error occurred.',
            variant: 'destructive'
        });
    } finally {
        isCreatingOrganisation.value = false;
    }
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button variant="success">New Organisation</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>New Organisation</DialogTitle>
                <DialogDescription>
                    Fill in the organisation details below and click save.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="name" class="text-right">Name</Label>
                    <Input id="name" placeholder="Organisation Name" class="col-span-3" v-model="organisation.name" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="cvr" class="text-right">CVR</Label>
                    <Input id="cvr" placeholder="12345678" class="col-span-3" v-model="organisation.cvr" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="country" class="text-right">Country</Label>
                    <Input id="country" placeholder="DK" maxlength="2" class="col-span-3 uppercase" v-model="organisation.country" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="address" class="text-right">Address</Label>
                    <Input id="address" placeholder="Street, City, Zip" class="col-span-3" v-model="organisation.address" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="zip" class="text-right">Zip</Label>
                    <Input id="zip" placeholder="Zip Code" class="col-span-3" v-model="organisation.zip" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label class="text-right">Type</Label>
                    <Select v-model="organisation.type" class="w-100">
                        <SelectTrigger class="col-span-3">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="organisation">Organisation</SelectItem>
                            <SelectItem value="forening/club">Forening/Club</SelectItem>
                            <SelectItem value="company">Company</SelectItem>
                            <SelectItem value="school">School</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="createOrganisation()" :disabled="isCreatingOrganisation" variant="success">
                    <LoaderCircle v-if="isCreatingOrganisation" class="h-4 w-4 animate-spin" />
                    Create Organisation
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>