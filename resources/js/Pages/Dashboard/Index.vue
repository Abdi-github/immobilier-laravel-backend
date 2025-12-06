<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHeader from '@/Components/Shared/PageHeader.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import PropertyStatusChart from '@/Components/Dashboard/PropertyStatusChart.vue';
import LeadStatusChart from '@/Components/Dashboard/LeadStatusChart.vue';
import PropertyTrendChart from '@/Components/Dashboard/PropertyTrendChart.vue';
import QuickActions from '@/Components/Dashboard/QuickActions.vue';
import RecentProperties from '@/Components/Dashboard/RecentProperties.vue';

defineOptions({ layout: AdminLayout });

interface ChartData {
    labels: string[];
    data: number[];
}

interface RecentProperty {
    id: number;
    title: string;
    status: string;
    price: number;
    transaction_type: string;
    category: string | null;
    canton: string | null;
    city: string | null;
    owner: string | null;
    created_at: string;
}

interface Props {
    stats: {
        total_properties: number;
        active_properties: number;
        pending_properties: number;
        total_agencies: number;
        active_agencies: number;
        total_users: number;
        active_users: number;
        total_leads: number;
        new_leads: number;
        properties_this_month: number;
        leads_this_month: number;
    };
    propertyStatusChart: ChartData;
    leadStatusChart: ChartData;
    recentProperties: RecentProperty[];
    monthlyPropertyTrend: ChartData;
}

const props = defineProps<Props>();
</script>

<template>
    <PageHeader title="Dashboard" description="Overview of your real estate platform" />

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
            title="Total Properties"
            :value="stats.total_properties"
            icon="pi pi-building"
            color="blue"
        />
        <StatCard
            title="Published"
            :value="stats.active_properties"
            icon="pi pi-check-circle"
            color="green"
        />
        <StatCard
            title="Pending Review"
            :value="stats.pending_properties"
            icon="pi pi-clock"
            color="yellow"
        />
        <StatCard
            title="New Leads"
            :value="stats.new_leads"
            icon="pi pi-inbox"
            color="red"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <StatCard
            title="Agencies"
            :value="stats.total_agencies"
            icon="pi pi-briefcase"
            color="purple"
        />
        <StatCard
            title="Active Users"
            :value="stats.active_users"
            icon="pi pi-users"
            color="blue"
        />
        <StatCard
            title="Properties This Month"
            :value="stats.properties_this_month"
            icon="pi pi-calendar"
            color="green"
        />
        <StatCard
            title="Leads This Month"
            :value="stats.leads_this_month"
            icon="pi pi-chart-line"
            color="yellow"
        />
    </div>

    <!-- Charts Row -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <PropertyStatusChart
            :labels="propertyStatusChart.labels"
            :data="propertyStatusChart.data"
            title="Properties by Status"
        />
        <LeadStatusChart
            :labels="leadStatusChart.labels"
            :data="leadStatusChart.data"
            title="Leads by Status"
        />
        <QuickActions />
    </div>

    <!-- Trend Chart -->
    <div class="mt-6">
        <PropertyTrendChart
            :labels="monthlyPropertyTrend.labels"
            :data="monthlyPropertyTrend.data"
            title="Properties Added (Last 6 Months)"
        />
    </div>

    <!-- Recent Properties -->
    <div class="mt-6">
        <RecentProperties :properties="recentProperties" />
    </div>
</template>
