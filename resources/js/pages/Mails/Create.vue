<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, reactive, onMounted, watch } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/use-toast';
import Editor from '@tinymce/tinymce-vue';

const { toast } = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mails',
        href: '/mails',
    },
    {
        title: 'Create Mail',
        href: '/mails/create',
    },
];

const page = usePage();
const currentUser = page.props.auth.user;

const recipientGroups = page.props.recipientGroups || {};
const fromEmails = page.props.fromEmails || []; // Add this to get available from emails
console.log(recipientGroups);
const recipientGroup = ref();

// Form state - Updated to include from_email
const form = reactive({
    from_email: '', // Add this field
    subject: '',
    body: '',
    recipients: [
        { email: '', name: '', type: 'to' }
    ],
    status: 'draft'
});

// TinyMCE configuration
const editorConfig = {
    height: 400,
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
        'preview', 'anchor', 'searchreplace', 'visualblocks',
        'code', 'fullscreen', 'insertdatetime', 'media', 'table',
        'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | ' +
        'bold italic forecolor backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | help',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
};

// Add new recipient field
const addRecipient = () => {
    form.recipients.push({ email: '', name: '', type: 'to' });
};

// Remove recipient field
const removeRecipient = (index: number) => {
    if (form.recipients.length > 1) {
        form.recipients.splice(index, 1);
    }
};

// Handle form submission
const submitForm = () => {
    // Validation
    if (!form.from_email.trim()) {
        toast({
            title: 'Validation Error',
            description: 'Please select a from email address.',
            variant: 'destructive'
        });
        return;
    }

    if (!form.subject.trim()) {
        toast({
            title: 'Validation Error',
            description: 'Please enter a subject for the email.',
            variant: 'destructive'
        });
        return;
    }

    if (!form.body.trim()) {
        toast({
            title: 'Validation Error',
            description: 'Please enter the email body.',
            variant: 'destructive'
        });
        return;
    }

    // Validate recipients
    const validRecipients = form.recipients.filter(recipient => 
        recipient.email.trim() !== ''
    );

    if (validRecipients.length === 0) {
        toast({
            title: 'Validation Error',
            description: 'Please add at least one recipient.',
            variant: 'destructive'
        });
        return;
    }

    // Prepare data for submission - Include from_email
    const formData = {
        from_email: form.from_email,
        subject: form.subject,
        body: form.body,
        recipients: validRecipients,
        status: form.status
    };

    // Submit to backend
    router.post('/mails', formData, {
        onSuccess: () => {
            toast({
                title: 'Email Saved',
                description: 'The email has been saved successfully.',
                variant: 'success'
            });
            
            // Redirect to mails list
            router.visit('/mails');
        },
        onError: (errors) => {
            console.error('Submission errors:', errors);
            toast({
                title: 'Error',
                description: 'Failed to save the email. Please try again.',
                variant: 'destructive'
            });
        }
    });
};

// Send email function
const sendEmail = () => {
    // Set status to sent
    form.status = 'sent';
    
    // Validate from email
    if (!form.from_email.trim()) {
        toast({
            title: 'Validation Error',
            description: 'Please select a from email address before sending.',
            variant: 'destructive'
        });
        return;
    }
    
    // Validate recipients
    const validRecipients = form.recipients.filter(recipient => 
        recipient.email.trim() !== ''
    );

    if (validRecipients.length === 0) {
        toast({
            title: 'Validation Error',
            description: 'Please add at least one recipient before sending.',
            variant: 'destructive'
        });
        return;
    }

    // Prepare data for submission - Include from_email
    const formData = {
        from_email: form.from_email,
        subject: form.subject,
        body: form.body,
        recipients: validRecipients,
        status: 'sent'
    };

    // Submit to backend
    fetch('/mails', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-Inertia': 'true',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(formData),
        credentials: 'same-origin',
    })
    .then(async response => {
        if (response.ok) {
            toast({
                title: 'Email Sent',
                description: 'The email has been sent successfully.',
                variant: 'success'
            });
            // Redirect to mails list
            router.visit('/mails');
        } else {
            const errorData = await response.json();
            console.error('Submission errors:', errorData);
            toast({
                title: 'Error',
                description: 'Failed to send the email. Please try again.',
                variant: 'destructive'
            });
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        toast({
            title: 'Error',
            description: 'Failed to send the email. Please try again.',
            variant: 'destructive'
        });
    });
};

// Initialize TinyMCE when component mounts
onMounted(() => {
    // Set default from email if available
    if (fromEmails.length > 0) {
        form.from_email = fromEmails[0].email;
    }
});

watch(recipientGroup, (newGroup) => {
    const group = recipientGroups[newGroup];
    if (group) {
        form.recipients = group.map(customer => ({ email: customer.email, name: '', type: 'to' }));
    }
});
</script>

<template>
    <Head title="Create Mail" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card class="max-w-6xl mx-auto">
                <CardHeader>
                    <CardTitle>Create New Email</CardTitle>
                    <CardDescription>
                        Compose and send your email or save as draft
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- From Email Field -->
                    <div class="space-y-2">
                        <Label for="from_email">From</Label>
                        <Select v-model="form.from_email">
                            <SelectTrigger>
                                <SelectValue placeholder="Select from email address" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="fromEmail in fromEmails"
                                    :key="fromEmail.email"
                                    :value="fromEmail.email"
                                >
                                    {{ fromEmail.name ? `${fromEmail.name} <${fromEmail.email}>` : fromEmail.email }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Subject Field -->
                    <div class="space-y-2">
                        <Label for="subject">Subject</Label>
                        <Input 
                            id="subject"
                            v-model="form.subject"
                            placeholder="Enter email subject"
                            type="text"
                        />
                    </div>

                    <!-- HTML Editor -->
                    <div class="space-y-2">
                        <Label for="body">Email Body</Label>
                        <Editor
                            api-key="tic9eoey0yqym7o9izvje3s99krk6ye0tda2zol2ark110dl"
                            v-model="form.body"
                            :init="editorConfig"
                            class="border rounded-lg"
                        />
                    </div>

                    <!-- Recipients Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold">Recipients</h3>
                        
                        <div 
                            v-for="(recipient, index) in form.recipients" 
                            :key="index"
                            class="flex flex-col sm:flex-row gap-2 items-start sm:items-center p-3 border rounded-lg"
                        >
                            <div class="flex-1 w-full">
                                <Label for="email">Email Address</Label>
                                <Input
                                    :id="'email-' + index"
                                    v-model="recipient.email"
                                    placeholder="recipient@example.com"
                                    type="email"
                                />
                            </div>
                            
                            <div class="w-full sm:w-32">
                                <Label for="type">Type</Label>
                                <Select v-model="recipient.type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="to">To</SelectItem>
                                        <SelectItem value="cc">CC</SelectItem>
                                        <SelectItem value="bcc">BCC</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div class="flex items-center justify-center mt-4 sm:mt-0">
                                <Button 
                                    type="button" 
                                    variant="destructive"
                                    size="sm"
                                    @click="removeRecipient(index)"
                                    :disabled="form.recipients.length <= 1"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                        
                        <Button 
                            type="button" 
                            variant="outline"
                            @click="addRecipient"
                        >
                            Add Recipient
                        </Button>
                        <Select v-model="recipientGroup">
                            <SelectTrigger>
                                <SelectValue placeholder="Select group" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(group, key) in recipientGroups"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ key }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Status Selection -->
                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="sent">Sent</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <Button 
                            type="button" 
                            @click="submitForm"
                            variant="default"
                        >
                            Save as Draft
                        </Button>
                        
                        <Button 
                            type="button" 
                            @click="sendEmail"
                            variant="success"
                        >
                            Send Email
                        </Button>
                        
                        <Button 
                            type="button" 
                            variant="secondary"
                            @click="$router.visit('/mails')"
                        >
                            Cancel
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Additional styling if needed */
</style>