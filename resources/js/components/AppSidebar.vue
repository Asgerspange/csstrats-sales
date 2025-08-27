<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, User, Users, Contact, Package, Mail, Receipt } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { ref, onMounted } from 'vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/',
        icon: LayoutGrid,
    },
    {
        title: 'Mails',
        href: '/mails',
        icon: Mail,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    }
];

const salesNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/sales',
        icon: LayoutGrid,
    },
    {
        title: 'Customers',
        href: '/sales/customers',
        icon: User,
    },
    {
        title: 'Organisations',
        href: '/sales/organisations',
        icon: Users,
    },
    {
        title: 'Contacts',
        href: '/sales/contacts',
        icon: Contact,
    },
    {
        title: 'Packages',
        href: '/sales/packages',
        icon: Package,
    },
    {
        title: 'Billing',
        href: '/sales/billing',
        icon: Receipt,
    }
]

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    {
        title: 'Dashboard',
        href: '/',
        icon: Folder,
    },
    {
        title: 'Sales',
        href: '/sales',
        icon: Folder,
    },
    {
        title: 'Admin',
        href: '/admin/users',
        icon: Folder,
    },
    // {
    //     title: 'Docs',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];

const selectedItems = ref(mainNavItems);
const selectedFooterItems = ref(footerNavItems);
onMounted(() => {
    let currentRoute = route().current();
    console.log(currentRoute)
    if (currentRoute.includes('admin')) {
        selectedItems.value = adminNavItems;
        selectedFooterItems.value = footerNavItems.filter(item => item.title !== 'Admin');
    } else if(currentRoute.includes('sales')) {
        selectedItems.value = salesNavItems;
        selectedFooterItems.value = footerNavItems.filter(item => item.title !== 'Sales');
    } else {
        selectedItems.value = mainNavItems;
        selectedFooterItems.value = footerNavItems.filter(item => item.title !== 'Dashboard');

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
