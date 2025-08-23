<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem } from '@/types';
    import { Head } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import { Input } from "@/components/ui/input";
    import { Label } from "@/components/ui/label";
    import { Button } from "@/components/ui/button";
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
    import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger } from "@/components/ui/combobox"
    import { Textarea } from "@/components/ui/textarea";
    import { CreateContactDialog } from '@/components/contacts';
    import { Check, ChevronsUpDown, Search } from "lucide-vue-next"
    import { cn } from '@/lib/utils';
    const props = defineProps({
        organisation: {
            type: Object,
            required: true
        },
        customers: {
            type: Array,
            required: true
        }
    });

    let originalCustomer = props.organisation.customer;

    console.log('Organisation:', props.organisation);
    const tasks = ref([
        // { task: 'Opfølgning', status: '-', date: '03-10-2025 10:25', duration: '0', seller: 'Andreas Barlebo Volder', salesCode: '0', note: 'Dorthe Bossen skal fjernes som bruger da' },
        // { task: 'Note', status: 'Intet', date: '04-03-2025 10:24', duration: '0', seller: 'Andreas Barlebo Volder', salesCode: '70', note: 'Helle Odum <helle.odum@wanzl.com> opsiger abonnement' },
        // { task: 'Note', status: 'Intet', date: '04-03-2025 10:24', duration: '0', seller: 'Andreas Barlebo Volder', salesCode: '71', note: 'Helle Odum <helle.odum@wanzl.com> reducerer fra 4 - 3' },
        // { task: 'Ingen', status: '-', date: '17-10-2024 10:48', duration: '0', seller: 'Silas Juhl Nielsen', salesCode: '0', note: 'helle køber business engelsk / tysk' }
    ]);

    const agreements = ref([]);
    
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organisations', href: '/organisations' },
        { title: props.organisation.name, href: `/organisations/${props.organisation.id}` }
    ];

    const updateCustomer = () => {
        let customer = props.organisation.customer;
        if (customer) {
            // Store the original customer ID before making the API call
            const originalCustomerId = originalCustomer?.id;
            
            fetch(`/organisations/${props.organisation.id}/change-customer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ cus_id: customer.id }),
            }).then(response => {
                if (response.ok) {
                    // Remove original customer from contacts if it exists
                    if (originalCustomerId) {
                        props.organisation.contacts = props.organisation.contacts.filter(contact => 
                            contact.cus_id !== originalCustomerId
                        );
                    }
                    // Update the original customer reference
                    originalCustomer = customer;
                } else {
                    // Revert the customer selection if the API call failed
                    props.organisation.customer = originalCustomer;
                }
            }).catch(error => {
                console.error('Error updating customer:', error);
                // Revert the customer selection if there's an error
                props.organisation.customer = originalCustomer;
            });
        }
    };

    const makePrimaryContact = (contactId: number) => {
        let contact = props.organisation.contacts.find(c => c.id === contactId);
        if (contact.is_primary) {
            console.log('Contact is already primary:', contactId);
            return;
        }

        fetch(`/organisations/${props.organisation.id}/make-primary-contact`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ contact_id: contactId }),
        }).then(response => {
            if (response.ok) {
                props.organisation.contacts.forEach(contact => {
                    contact.is_primary = false;
                });
                const contact = props.organisation.contacts.find(c => c.id === contactId);
                if (contact) {
                    contact.is_primary = true;
                }
            } else {
                console.error('Failed to update primary contact');
            }
        });
    };

    const removeContact = (contactId: number) => {
        fetch(`/organisations/${props.organisation.id}/remove-contact`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ contact_id: contactId }),
        });
    };

    const updateNotes = () => {
        fetch(`/organisations/${props.organisation.id}/update-notes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ notes: props.organisation.notes }),
        });
    };
</script>

<template>
    <Head :title="`Organisation - ${organisation.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col p-4 overflow-x-auto h-full">
            <h1 class="text-2xl font-bold mb-4">{{ organisation.name }}</h1>

            <div class="grid grid-cols-1 lg:grid-cols-[2fr_3fr] gap-6 h-full">
                <div class="space-y-3 bg-white rounded-xl p-4 shadow">
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Navn</Label>
                        <Input :modelValue="organisation.name" readonly />
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">CVR</Label>
                        <Input :modelValue="organisation.cvr" readonly />
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Adresse</Label>
                        <Input :modelValue="`${organisation.address}, ${organisation.zip}`" readonly />
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Land</Label>
                        <Input :modelValue="organisation.country" readonly />
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Kunde</Label>
                        <Combobox v-model="organisation.customer" by="id" @update:model-value="updateCustomer">
                            <ComboboxAnchor as-child class="!w-full">
                                <ComboboxTrigger as-child>
                                    <Button variant="outline" class="justify-between w-full">
                                        {{ organisation.customer?.name || 'Vælg kunde' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </ComboboxTrigger>
                            </ComboboxAnchor>
                            <ComboboxList class="!w-full">
                                 <div class="relative w-full max-w-sm items-center">
                                    <ComboboxInput class="pl-9 focus-visible:ring-0 border-0 border-b rounded-none h-10" placeholder="Search customers" />
                                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                                    <Search class="size-4 text-muted-foreground" />
                                    </span>
                                </div>

                                <ComboboxEmpty>
                                    <div class="p-2 text-sm text-gray-500">Ingen kunder fundet.</div>
                                </ComboboxEmpty>
                                <ComboboxGroup>
                                    <ComboboxItem
                                        v-for="customer in props.customers"
                                        :key="customer.id"
                                        :value="customer"
                                    >
                                        {{ customer.name }} - {{ customer.email }}
                                        <ComboboxItemIndicator>
                                            <Check :class="cn('ml-auto h-4 w-4')" />
                                        </ComboboxItemIndicator>
                                    </ComboboxItem>
                                </ComboboxGroup>
                            </ComboboxList>
                        </Combobox>
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Valuta</Label>
                        <Input :modelValue="organisation.customer?.currency" readonly />
                    </div>
                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Type</Label>
                        <Select v-model="organisation.type" class="w-full">
                            <SelectTrigger class="col-span-3 w-full">
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

                    <div class="flex flex-col">
                        <Label class="font-semibold capitalize">Noter</Label>
                        <Textarea v-model="organisation.notes" class="mb-2" placeholder="Skriv noter her..." />
                        <Button @click="updateNotes()">Gem noter</Button>
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="bg-white rounded-xl p-4 shadow flex-1 overflow-auto max-h-[400px] h-100">
                        <h2 class="font-semibold mb-2">Opgaveliste</h2>
                        <table class="min-w-full border text-sm">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="p-2 text-left">Opgave</th>
                                    <th>Status</th>
                                    <th>Dato</th>
                                    <th>Varighed</th>
                                    <th>Sælger</th>
                                    <th>Salgskode</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(t, i) in tasks" :key="i" class="border-t">
                                    <td class="p-2">{{ t.task }}</td>
                                    <td>{{ t.status }}</td>
                                    <td>{{ t.date }}</td>
                                    <td>{{ t.duration }}</td>
                                    <td>{{ t.seller }}</td>
                                    <td>{{ t.salesCode }}</td>
                                    <td>{{ t.note }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Contacts -->
                    <div class="bg-white rounded-xl p-4 shadow">
                        <div class="flex justify-between mb-2 items-center">
                            <h2 class="font-semibold">Kontakter</h2>
                            <CreateContactDialog :organisation="organisation" />
                        </div>

                        <div class="max-h-[200px] h-100 overflow-y-auto">
                            <table class="min-w-full border text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">Kontaktperson</th>
                                        <th class="p-2 text-left">Jobfunktion</th>
                                        <th class="p-2 text-left">Telefon</th>
                                        <th class="p-2 text-left">Email</th>
                                        <th class="p-2 text-left">Handling</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        class="border-t"
                                        :class="{
                                            'bg-green-100': organisation.contacts?.find(c => c.cus_id === organisation.customer?.id && c.is_primary)
                                        }"
                                    >
                                        <td class="p-2">{{ organisation.customer?.name }}</td>
                                        <td class="p-2">Stripe bruger</td>
                                        <td class="p-2">Ikke angivet</td>
                                        <td class="p-2">{{ organisation.customer?.email || 'Ikke angivet' }}</td>
                                        <td class="p-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                @click="makePrimaryContact(organisation.contacts?.find(c => c.cus_id === organisation.customer?.id)?.id)"
                                            >
                                                Sæt primær
                                            </Button>
                                        </td>
                                    </tr>
                                    <template v-for="(contact, index) in organisation.contacts" :key="index">
                                        <tr class="border-t" :class="{
                                            'bg-green-100': contact.is_primary
                                        }" v-if="contact.cus_id !== organisation.customer.id">
                                            <td class="p-2">{{ contact?.customer?.name ?? contact.contact?.name }}</td>
                                            <td class="p-2">{{ contact.contact?.job ?? 'Ikke angivet' }}</td>
                                            <td class="p-2">{{ contact.contact?.phone ?? 'Ikke angivet' }}</td>
                                            <td class="p-2">{{ contact.customer?.email ?? contact.contact?.email ?? 'Ikke angivet' }}</td>
                                            <td class="p-2 flex gap-2">
                                                <Button variant="outline" size="sm" @click="makePrimaryContact(contact.id)">Sæt primær</Button>
                                                <Button variant="destructive" size="sm" @click="removeContact(contact.id)">Fjern</Button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- Agreements -->
                    <div class="bg-white rounded-xl p-4 shadow max-h-[200px] overflow-y-auto h-100">
                        <h2 class="font-semibold mb-2">Aftaler</h2>
                        <div v-if="agreements.length === 0" class="text-gray-500">Ingen aftaler</div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-[2fr_3fr] gap-6 mt-6">
                <div class="bg-white rounded-xl p-4 shadow">
                    <h2 class="font-semibold mb-2">Hold</h2>
                    <table class="min-w-full border text-sm" v-if="props.organisation?.customer?.user?.owned_teams.length">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Navn</th>
                                <th class="p-2 text-left">Medlemmer</th>
                                <th class="p-2 text-left">Stratbooks</th>
                                <th class="p-2 text-left">Strats</th>
                                <!-- <th class="p-2 text-left">Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t" v-for="team in props.organisation?.customer?.user?.owned_teams" :key="team.id">
                                <td class="p-2">{{ team.name }}</td>
                                <td class="p-2">{{ team.users_count }}</td>
                                <td class="p-2">{{ team.stratbooks_count }}</td>
                                <td class="p-2">{{ team.tactics_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.organisation?.customer?.user?.owned_teams.length === 0" class="text-gray-500">Ingen hold</div>
                </div>
                <div class="bg-white rounded-xl p-4 shadow">
                    <h2 class="font-semibold mb-2">Pakker</h2>
                    <table class="min-w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Navn</th>
                                <th class="p-2 text-left">Pris</th>
                                <th class="p-2 text-left">Månedlig Pris</th>
                                <th class="p-2 text-left">Maks. Medlemmer</th>
                                <th class="p-2 text-left">Maks. Teams</th>
                                <th class="p-2 text-left">Maks. Stratbooks</th>
                                <!-- <th class="p-2 text-left">Actions</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in organisation.packages" :key="p.id" class="border-t">
                                <td class="p-2">{{ p.name }}</td>
                                <td class="p-2">€{{ p.price }}</td>
                                <td class="p-2">€{{ p.monthly_price }}</td>
                                <td class="p-2">{{ p.max_members }}</td>
                                <td class="p-2">{{ p.max_teams }}</td>
                                <td class="p-2">{{ p.max_stratbooks }}</td>
                                <!-- <td class="p-2">
                                    <Button variant="destructive" size="sm">Slet</Button>
                                </td> -->
                            </tr>
                        </tbody>
                    </table>
                    <!-- <Button variant="outline" size="sm" @click="createPackage">Opret pakke</Button> -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
