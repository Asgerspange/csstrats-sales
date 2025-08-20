<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, User, Users, Contact, Package, Mail } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { ref, onMounted } from 'vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/',
        icon: LayoutGrid,
    },
    {
        title: 'Stripe Customers',
        href: '/customers',
        icon: User,
    },
    {
        title: 'Organisations',
        href: '/organisations',
        icon: Users,
    },
    {
        title: 'Contacts',
        href: '/contacts',
        icon: Contact,
    },
    {
        title: 'Packages',
        href: '/packages',
        icon: Package,
    },
    {
        title: 'Mails',
        href: '/mails',
        icon: Mail,
    }
];

const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Roles',
        href: '/admin/roles',
        icon: Users,
    },
    {
        title: 'Permissions',
        href: '/admin/permissions',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Admin',
        href: '/admin/users',
        icon: BookOpen,
    },
    {
        title: 'Docs',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

const adminFooterNavItems: NavItem[] = [
    {
        title: 'Sales',
        href: '/',
        icon: Folder,
    },
];

const selectedItems = ref(mainNavItems);
const selectedFooterItems = ref(footerNavItems);
onMounted(() => {
    if (route().current().includes('admin')) {
        selectedItems.value = adminNavItems;
        selectedFooterItems.value = adminFooterNavItems;
    } else {
        selectedItems.value = mainNavItems;
        selectedFooterItems.value = footerNavItems;
    }
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('home')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="selectedItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="selectedFooterItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
