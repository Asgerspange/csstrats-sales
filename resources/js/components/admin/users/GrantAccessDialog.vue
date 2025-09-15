<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { CalendarDate, DateFormatter, getLocalTimeZone, parseDate } from "@internationalized/date"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Calendar } from "@/components/ui/calendar"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { LoaderCircle, Calendar as CalendarIcon } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import { cn } from "@/lib/utils"
import { useToast } from '@/components/ui/toast/use-toast'
const { toast } = useToast()

const access = ref({
    max_teams: 0,
    max_members: 0,
    max_stratbooks: 0,
    expires_at: null as string | null,
})

import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
const isGranting = ref(false);
const dialogOpen = ref(false);

// Local calendar date state
const expiresAtDate = ref<CalendarDate | null>(null)

// Keep access.expires_at in sync with Calendar
watch(expiresAtDate, (val) => {
    access.value.expires_at = val ? val.toString() : null
})

const user = usePage().props.user;

const grantAccess = async () => {
    isGranting.value = true;
    const formattedAccess = { ...access.value };
    try {
        const response = await fetch('/admin/users/grant-access', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                user_id: user.id,
                max_teams: Number(formattedAccess.max_teams),
                max_members: Number(formattedAccess.max_members),
                max_stratbooks: Number(formattedAccess.max_stratbooks),
                expires_at: formattedAccess.expires_at,
            }),
        });

        if (response.status === 200 || response.status === 301 || response.status === 302 || response.status === 303) {
            const data = await response.json();
            toast({
                title: 'Organisation Created',
                description: `${data.organisation.name} has been created successfully.`,
                variant: 'success'
            });

            access.value = { max_teams: 0, max_members: 0, max_stratbooks: 0, expires_at: null };

            expiresAtDate.value = null
            dialogOpen.value = false;
            router.reload({ only: ['user'] });
        } else if (response.status === 409) {
            toast({
                title: 'Grant Access Already Exists',
                description: `The user already has granted access`,
                variant: 'destructive'
            });
        } else {
            const err = await response.json();
            toast({
                title: 'Grant Access Failed',
                description: err?.message || 'Grant Access could not be completed.',
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
        isGranting.value = false;
    }
};

const df = new DateFormatter("da-DK", {
    dateStyle: "long",
});
function toDate(dateStr: string) {
    return parseDate(dateStr).toDate(getLocalTimeZone());
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogTrigger as-child>
            <Button variant="success">Grant Access</Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Grant Access</DialogTitle>
                <DialogDescription>
                    Fill in the access details below and click save.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_teams">Max Teams</Label>
                    <Input id="max_teams" placeholder="Max Teams" class="col-span-3" v-model="access.max_teams" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_members">Max Members</Label>
                    <Input id="max_members" placeholder="Max Members" class="col-span-3" v-model="access.max_members" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="max_stratbooks">Max Stratbooks</Label>
                    <Input id="max_stratbooks" placeholder="Max Stratbooks" class="col-span-3" v-model="access.max_stratbooks" />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="expires_at">Expires At</Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="cn(
                                    'w-[240px] ps-3 text-start font-normal',
                                    !access.expires_at && 'text-muted-foreground',
                                )"
                            >
                                <span>{{ access.expires_at ? df.format(toDate(access.expires_at)) : "Pick a date" }}</span>
                                <CalendarIcon class="ms-auto h-4 w-4 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <Calendar
                                calendar-label="Expires At"
                                initial-focus
                                :min-value="new CalendarDate(1900, 1, 1)"
                                v-model="expiresAtDate"
                            />
                        </PopoverContent>
                    </Popover>
                </div>
            </div>
            <DialogFooter>
                <Button type="submit" @click="grantAccess()" :disabled="isGranting" variant="success">
                    <LoaderCircle v-if="isGranting" class="h-4 w-4 animate-spin" />
                    Grant Access
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
