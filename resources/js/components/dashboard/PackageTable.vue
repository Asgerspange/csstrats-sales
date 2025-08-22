<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Table, TableHeader, TableBody, TableRow, TableCell, TableHead } from '@/components/ui/table'; // Import shadcn components
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';

const { props } = usePage();
const products = props.packages; // Access the packages data from Inertia props
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Packages</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Subscribers</TableHead>
                            <TableHead>Currency</TableHead>
                            <TableHead>Max Teams</TableHead>
                            <TableHead>Max Members</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="product in products" :key="product.id">
                            <TableCell>{{ product.id }}</TableCell>
                            <TableCell>{{ product.name }}</TableCell>
                            <TableCell>{{ product.price }}</TableCell>
                            <TableCell>{{ product.subscribers }}</TableCell>
                            <TableCell>{{ product.currency }}</TableCell>
                            <TableCell>{{ product.max_teams }}</TableCell>
                            <TableCell>{{ product.max_members }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>