<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarInitials } from '@/components/ui/avatar';
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuItem,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import { useToast } from '@/components/ui/toast/use-toast';
import { 
    MoreVertical, 
    Mail, 
    User, 
    Calendar, 
    Users, 
    ArrowLeft,
    Edit,
    Trash2,
    Forward,
    Reply
} from 'lucide-vue-next';

const { toast } = useToast();

const props = defineProps<{
    email: {
        id: number;
        subject: string;
        body: string;
        sender: {
            id: number;
            name: string;
            email: string;
        };
        status: string;
        created_at: string;
        sent_at: string | null;
        recipients: Array<{
            id: number;
            recipient_email: string;
            recipient_name?: string;
            type: 'to' | 'cc' | 'bcc';
        }>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mails',
        href: '/mails',
    },
    {
        title: 'View Email',
        href: `/mails/${props.email.id}`,
    },
];

// Format date function
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get status badge variant
const getStatusVariant = (status: string) => {
    switch (status) {
        case 'sent':
            return 'success';
        case 'draft':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'default';
    }
};

// Group recipients by type
const groupedRecipients = computed(() => {
    const groups = {
        to: [] as typeof props.email.recipients,
        cc: [] as typeof props.email.recipients,
        bcc: [] as typeof props.email.recipients,
    };

    props.email.recipients.forEach(recipient => {
        groups[recipient.type].push(recipient);
    });

    return groups;
});

// Get initials for avatar
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Delete email function
const deleteEmail = () => {
    if (confirm('Are you sure you want to delete this email? This action cannot be undone.')) {
        router.delete(`/mails/${props.email.id}`, {
            onSuccess: () => {
                toast({
                    title: 'Email Deleted',
                    description: 'The email has been deleted successfully.',
                    variant: 'success'
                });
                router.visit('/mails');
            },
            onError: () => {
                toast({
                    title: 'Error',
                    description: 'Failed to delete the email. Please try again.',
                    variant: 'destructive'
                });
            }
        });
    }
};

// Resend email function (for failed emails)
const resendEmail = () => {
    router.post(`/mails/${props.email.id}/resend`, {}, {
        onSuccess: () => {
            toast({
                title: 'Email Resent',
                description: 'The email has been resent successfully.',
                variant: 'success'
            });
        },
        onError: () => {
            toast({
                title: 'Error',
                description: 'Failed to resend the email. Please try again.',
                variant: 'destructive'
            });
        }
    });
};
</script>

<template>
    <Head :title="`${email.subject} - Mail Details`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4 max-w-6xl">
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link href="/mails">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Mails
                        </Button>
                    </Link>
                    <Badge :variant="getStatusVariant(email.status)">
                        {{ email.status.charAt(0).toUpperCase() + email.status.slice(1) }}
                    </Badge>
                </div>
                
                <!-- Actions Dropdown -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="sm">
                            <MoreVertical class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <!-- <DropdownMenuItem @click="$router.visit(`/mails/${email.id}/edit`)">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="email.status === 'failed'" @click="resendEmail">
                            <Mail class="h-4 w-4 mr-2" />
                            Resend
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                            <Forward class="h-4 w-4 mr-2" />
                            Forward
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                            <Reply class="h-4 w-4 mr-2" />
                            Reply
                        </DropdownMenuItem>
                        <DropdownMenuSeparator /> -->
                        <DropdownMenuItem @click="deleteEmail" class="text-red-600">
                            <Trash2 class="h-4 w-4 mr-2" />
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div class="grid gap-6">
                <!-- Email Header Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-xl mb-2">{{ email.subject }}</CardTitle>
                                <CardDescription class="flex items-center gap-4 text-sm">
                                    <span class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        Created: {{ formatDate(email.created_at) }}
                                    </span>
                                    <span v-if="email.sent_at" class="flex items-center gap-2">
                                        <Mail class="h-4 w-4" />
                                        Sent: {{ formatDate(email.sent_at) }}
                                    </span>
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Sender and Recipients Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Sender & Recipients
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Sender -->
                        <div class="flex items-center gap-3 p-3 bg-gray-100 bg-opacity-5 rounded-lg">
                            <Avatar>
                                <AvatarFallback>
                                    {{ getInitials(email.sender.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <p class="font-medium">{{ email.sender.name }}</p>
                                <p class="text-sm opacity-70">{{ email.sender.email }}</p>
                            </div>
                            <Badge variant="outline">Sender</Badge>
                        </div>

                        <Separator />

                        <!-- Recipients by Type -->
                        <div class="space-y-4">
                            <!-- TO Recipients -->
                            <div v-if="groupedRecipients.to.length > 0">
                                <h4 class="text-sm font-medium opacity-70 mb-2 uppercase tracking-wider">
                                    To ({{ groupedRecipients.to.length }})
                                </h4>
                                <div class="space-y-2">
                                    <div 
                                        v-for="recipient in groupedRecipients.to" 
                                        :key="recipient.id"
                                        class="flex items-center gap-3 p-2 rounded-lg border"
                                    >
                                        <Avatar size="sm">
                                            <AvatarFallback>
                                                {{ getInitials(recipient.recipient_name || recipient.recipient_email) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">
                                                {{ recipient.recipient_name || recipient.recipient_email.split('@')[0] }}
                                            </p>
                                            <p class="text-xs opacity-60">{{ recipient.recipient_email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CC Recipients -->
                            <div v-if="groupedRecipients.cc.length > 0">
                                <h4 class="text-sm font-medium opacity-70 mb-2 uppercase tracking-wider">
                                    CC ({{ groupedRecipients.cc.length }})
                                </h4>
                                <div class="space-y-2">
                                    <div 
                                        v-for="recipient in groupedRecipients.cc" 
                                        :key="recipient.id"
                                        class="flex items-center gap-3 p-2 rounded-lg border"
                                    >
                                        <Avatar size="sm">
                                            <AvatarFallback>
                                                {{ getInitials(recipient.recipient_name || recipient.recipient_email) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">
                                                {{ recipient.recipient_name || recipient.recipient_email.split('@')[0] }}
                                            </p>
                                            <p class="text-xs opacity-60">{{ recipient.recipient_email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BCC Recipients -->
                            <div v-if="groupedRecipients.bcc.length > 0">
                                <h4 class="text-sm font-medium opacity-70 mb-2 uppercase tracking-wider">
                                    BCC ({{ groupedRecipients.bcc.length }})
                                </h4>
                                <div class="space-y-2">
                                    <div 
                                        v-for="recipient in groupedRecipients.bcc" 
                                        :key="recipient.id"
                                        class="flex items-center gap-3 p-2 rounded-lg border"
                                    >
                                        <Avatar size="sm">
                                            <AvatarFallback>
                                                {{ getInitials(recipient.recipient_name || recipient.recipient_email) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">
                                                {{ recipient.recipient_name || recipient.recipient_email.split('@')[0] }}
                                            </p>
                                            <p class="text-xs opacity-60">{{ recipient.recipient_email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Email Content Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Email Content</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div 
                            v-html="email.body"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>