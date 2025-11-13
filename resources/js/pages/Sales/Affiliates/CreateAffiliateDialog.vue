
<template>
    <div class="card flex justify-center">
        <Button label="Create Affiliate" @click="visible = true" />

        <Dialog v-model:visible="visible" modal header="Create Affiliate" class="w-[600px]">
            <Stepper value="1">
                <StepItem value="1">
                    <Step>Personal Information</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col gap-4">
                            <div class="flex gap-4">
                                <FloatLabel>
                                    <label for="name">Name</label>
                                    <InputText v-model="affiliate.name" id="name" class="w-full" />
                                </FloatLabel>
                                <FloatLabel>
                                    <label for="email">Email</label>
                                    <InputText v-model="affiliate.email" id="email" class="w-full" />
                                </FloatLabel>
                            </div>
                            <FloatLabel>
                                <label for="bank_account">Bank Account <small>Not Required</small></label>
                                <InputText v-model="affiliate.bank_account" id="bank_account" class="w-full" />
                            </FloatLabel>
                            <FloatLabel>
                                <label for="iban">IBAN <small>Not Required</small></label>
                                <InputText v-model="affiliate.iban" id="iban" class="w-full" />
                            </FloatLabel>
                            <div class="flex justify-end">
                                <SecondaryButton @click="activateCallback('2')">Next</SecondaryButton>
                            </div>
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="2">
                    <Step>Coupon Details</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col gap-4">
                            <FloatLabel>
                                <label for="coupon">Coupon</label>
                                <Select v-model="affiliate.coupon" id="coupon" class="w-full" 
                                    :optionLabel="option => option.coupon_id + ' - ' + (option.name || 'No Promo Code')"
                                    :optionValue="option => option.coupon_id"
                                    :options="props.coupons" filter />
                            </FloatLabel>
                            <div class="flex justify-between">
                                <SecondaryButton @click="activateCallback('1')">Back</SecondaryButton>
                                <SecondaryButton @click="activateCallback('3')">Next</SecondaryButton>
                            </div>
                        </div>
                    </StepPanel>
                </StepItem>
                <StepItem value="3">
                    <Step>Commission & Status</Step>
                    <StepPanel v-slot="{ activateCallback }">
                        <div class="flex flex-col gap-4">
                            <FloatLabel>
                                <label for="access_token">Access Token</label>
                                <InputText v-model="affiliate.access_token" id="access_token" class="w-full" />
                            </FloatLabel>
                            <FloatLabel>
                                <label for="commission_rate">Commission Rate (%)</label>
                                <InputText v-model="affiliate.commission_rate" id="commission_rate" type="number" class="w-full" />
                            </FloatLabel>
                            <FloatLabel>
                                <label for="min_payout_amount">Minimum Payout Amount</label>
                                <InputText v-model="affiliate.min_payout_amount" id="min_payout_amount" type="number" class="w-full" />
                            </FloatLabel>
                            <FloatLabel>
                                <label for="status">Status</label>
                                <InputText v-model="affiliate.status" id="status" class="w-full" />
                            </FloatLabel>
                            <div class="flex justify-between">
                                <SecondaryButton @click="activateCallback('2')">Back</SecondaryButton>
                                <Button label="Create Affiliate" @click="createAffiliate()" />
                            </div>
                        </div>
                    </StepPanel>
                </StepItem>
            </Stepper>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Dialog from '@/volt/Dialog.vue';
import InputText from '@/volt/InputText.vue';
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Stepper from '@/volt/Stepper.vue';
import Step from '@/volt/Step.vue';
import StepItem from '@/volt/StepItem.vue';
import StepPanel from '@/volt/StepPanel.vue';
import FloatLabel from 'primevue/floatlabel';
import Select from '@/volt/Select.vue';
const page = usePage<any>();
const props = page.props
const affiliate = ref({
    name: '',
    email: '',
    bank_account: '',
    iban: '',
    coupon: null,
    commission_rate: 0,
    min_payout_amount: 0,
    status: 'active',
    access_token: '',
});

const visible = ref(false);

const createAffiliate = () => {
    fetch('/sales/affiliates', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(affiliate.value),
    }).then(response => {
        if (response.ok) {
            visible.value = false;
            // Optionally, reset the affiliate form or show a success message
        } else {
            // Handle error response
            console.error('Failed to create affiliate');
        }
    });
};
</script>
