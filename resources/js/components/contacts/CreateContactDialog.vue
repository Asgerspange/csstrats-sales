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
const props = defineProps([ 'organisation' ])

const { toast } = useToast()
const contact = ref({
    name: '',
    email: '',
    phone: '',
    country: '',
    job: '',
    organisation_id: props.organisation?.id ?? '',
})


const isCreatingContact = ref(false);
const dialogOpen = ref(false);

const createContact = async () => {
    isCreatingContact.value = true;

    let formattedContact = {
        ...contact.value,
        country: contact.value.country.toUpperCase(),
    };

    try {
        const response = await fetch('/contacts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(formattedContact),
        });

        if (response.status === 201) {
            const data = await response.json();

            toast({
                title: 'Contact Created',
                description: `${data.contact.name} has been created successfully.`,
                variant: 'success'
            });

            contact.value = { name: '', email: '', phone: '', country: '', job: '', organisation_id: '' };
            dialogOpen.value = false;

            const only = ['contacts'];
            if (props.organisation) {
                only.push('organisation');
            }
            router.reload({ only });
        } else if (response.status === 409) {
            toast({
                title: 'Email Already Exists',
                description: `${contact.value.name} could not be created because the email already exists.`,
                variant: 'destructive'
            });

        } else {
            const err = await response.json();
            toast({
                title: 'Contact Creation Failed',
                description: err?.message || 'Contact could not be created.',
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
        isCreatingContact.value = false;
    }
};
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button variant="success">New Contact</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>New Contact</DialogTitle>
                <DialogDescription>
                    Fill in the contact details below and click save.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="name" class="text-right">Name</Label>
                    <Input id="name" placeholder="Contact Name" class="col-span-3" v-model="contact.name" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="email" class="text-right">Email</Label>
                    <Input id="email" placeholder="Email" class="col-span-3" v-model="contact.email" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="phone" class="text-right">Phone</Label>
                    <Input id="phone" placeholder="Phone" class="col-span-3" v-model="contact.phone" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="country" class="text-right">Country</Label>
                    <Input id="country" placeholder="DK" maxlength="2" class="col-span-3 uppercase" v-model="contact.country" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="job" class="text-right">Job</Label>
                    <Input id="job" placeholder="Job Title" class="col-span-3" v-model="contact.job" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label class="text-right">Organisation</Label>
                    <template v-if="organisation">
                        <Input :modelValue="organisation.name" readonly class="w-fit" />
                    </template>
                    <template v-else>
                        <Select v-model="contact.organisation_id" class="w-100">
                            <SelectTrigger class="col-span-3">
                                <SelectValue placeholder="Select organisation" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="org in $page.props.organisations" :key="org.id" :value="org.id">
                                    {{ org.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </template>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="createContact()" :disabled="isCreatingContact" variant="success">
                    <LoaderCircle v-if="isCreatingContact" class="h-4 w-4 animate-spin" />
                    Create Contact
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>