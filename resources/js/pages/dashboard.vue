<!-- resources/js/views/dashboard/Index.vue -->
<template>
    <div>
        <!-- Welcome Card -->
        <CCard class="mb-4 bg-gradient-primary text-white">
            <CCardBody>
                <CRow class="align-items-center">
                    <CCol :md="8">
                        <h1 class="display-6 mb-2">Welcome back, {{ name }}! 👋</h1>
                        <p class="mb-0">Here's what's happening with your inventory today.</p>
                        <div class="mt-2">
                            <CBadge color="light" class="me-2">
                                <CIcon :icon="cilCalendar" class="me-1" />
                                {{ currentDate }}
                            </CBadge>
                            <CBadge color="light">
                                <CIcon :icon="cilClock" class="me-1" />
                                {{ currentTime }}
                            </CBadge>
                        </div>
                    </CCol>
                    <CCol :md="4" class="text-end d-none d-md-block">
                        <CIcon :icon="cilChartLine" size="4xl" class="opacity-25" />
                    </CCol>
                </CRow>
            </CCardBody>
        </CCard>

        <!-- Quick Stats Cards -->
        <CRow class="mb-4">
            <CCol :md="3">
                <CCard class="h-100">
                    <CCardBody class="text-center">
                        <div class="text-muted text-uppercase small mb-2">Total Products</div>
                        <h2 class="mb-0">{{ stats.total_products || 0 }}</h2>
                        <CProgress color="primary" :value="stats.products_vs_target || 0" class="mt-2" />
                        <small class="text-muted">{{ stats.products_vs_target || 0 }}% of target</small>
                    </CCardBody>
                </CCard>
            </CCol>

            <CCol :md="3">
                <CCard class="h-100">
                    <CCardBody class="text-center">
                        <div class="text-muted text-uppercase small mb-2">Today's Sales</div>
                        <h2 class="mb-0">PKR {{ formatCurrency(stats.today_sales || 0) }}</h2>
                        <div class="mt-2 d-flex align-items-center justify-content-center">
                            <CIcon :icon="stats.sales_trend === 'up' ? cilArrowTop : cilArrowBottom"
                                   :class="stats.sales_trend === 'up' ? 'text-success' : 'text-danger'" />
                            <small :class="stats.sales_trend === 'up' ? 'text-success' : 'text-danger'">
                                {{ stats.sales_change || 0 }}% vs yesterday
                            </small>
                        </div>
                    </CCardBody>
                </CCard>
            </CCol>

            <CCol :md="3">
                <CCard class="h-100">
                    <CCardBody class="text-center">
                        <div class="text-muted text-uppercase small mb-2">Low Stock Items</div>
                        <h2 class="mb-0 text-warning">{{ stats.low_stock_count || 0 }}</h2>
                        <small class="text-warning">
                            <CIcon :icon="cilWarning" class="me-1" />
                            Need attention
                        </small>
                    </CCardBody>
                </CCard>
            </CCol>

            <CCol :md="3">
                <CCard class="h-100">
                    <CCardBody class="text-center">
                        <div class="text-muted text-uppercase small mb-2">Monthly Profit</div>
                        <h2 class="mb-0 text-success">PKR {{ formatCurrency(stats.monthly_profit || 0) }}</h2>
                        <small class="text-success">
                            <CIcon :icon="cilArrowTop" class="me-1" />
                            {{ stats.profit_margin || 0 }}% margin
                        </small>
                    </CCardBody>
                </CCard>
            </CCol>
        </CRow>

        <!-- Charts Row -->
        <CRow class="mb-4">
            <CCol :md="8">
    <CCard class="h-100">
        <CCardHeader>
            <h5 class="mb-0">Profit & Loss Trend</h5>
            <small class="text-muted">Last 30 days performance</small>
        </CCardHeader>
        <CCardBody>
            <div v-if="loadingChart" class="text-center py-6">
                <CSpinner />
                <p class="mt-2">Loading chart...</p>
            </div>
            <div v-else-if="profitData.length === 0" class="text-center py-6">
                <CIcon :icon="cilChart" size="3xl" class="text-muted mb-3" />
                <p>No profit data available</p>
            </div>
            <div v-else class="chart-container">
                <canvas ref="profitChart"></canvas>
            </div>
        </CCardBody>
    </CCard>
</CCol>

  <CCol :md="4">
    <CCard class="h-100">
        <CCardHeader>
            <h5 class="mb-0">Transaction Distribution</h5>
            <small class="text-muted">Recent activities by type</small>
        </CCardHeader>
        <CCardBody>
            <div v-if="loadingChart" class="text-center py-6">
                <CSpinner />
            </div>
            <div v-else-if="transactionDistribution.length === 0" class="text-center py-6">
                <CIcon :icon="cilChartPie" size="3xl" class="text-muted mb-3" />
                <p>No transaction data available</p>
            </div>
            <div v-else>
                <!-- Add this container div -->
                <div class="chart-container">
                    <canvas ref="transactionChart"></canvas>
                </div>
                <!-- Legend below chart -->
                <div class="mt-3">
                    <div class="d-flex justify-content-center flex-wrap gap-2">
                        <div v-for="(item, index) in transactionDistribution" :key="index"
                             class="d-flex align-items-center me-3">
                            <div class="legend-dot me-2"
                                 :style="{ backgroundColor: item.color, width: '10px', height: '10px', borderRadius: '50%' }"></div>
                            <small>
                                {{ item.type }}: {{ item.count }} ({{ item.percentage }}%)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </CCardBody>
    </CCard>
</CCol>

        </CRow>

        <!-- Low Stock Alerts -->
        <CCard class="mb-4">
            <CCardHeader class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Low Stock Alerts</h5>
                    <small class="text-muted">Products below minimum quantity</small>
                </div>
                <CBadge color="danger" class="fs-6">{{ lowStockProducts.length }} items</CBadge>
            </CCardHeader>
            <CCardBody>
                <div v-if="loading" class="text-center py-6">
                    <CSpinner />
                    <p class="mt-2">Loading low stock products...</p>
                </div>

                <div v-else-if="lowStockProducts.length === 0" class="text-center py-6">
                    <CIcon :icon="cilCheckCircle" size="3xl" class="text-success mb-3" />
                    <h5 class="text-h5">All Good!</h5>
                    <p class="text-body-1 text-medium-emphasis">
                        No products are below minimum stock level
                    </p>
                </div>

                <div v-else>
                    <CTable hover responsive>
                        <CTableHead>
                            <CTableRow>
                                <CTableHeaderCell>Product</CTableHeaderCell>
                                <CTableHeaderCell>Category</CTableHeaderCell>
                                <CTableHeaderCell>Current Stock</CTableHeaderCell>
                                <CTableHeaderCell>Minimum Required</CTableHeaderCell>
                                <CTableHeaderCell>Status</CTableHeaderCell>
                                <CTableHeaderCell class="text-end">Action</CTableHeaderCell>
                            </CTableRow>
                        </CTableHead>
                        <CTableBody>
                          <CTableRow v-for="product in lowStockProducts" :key="product.id">
        <CTableDataCell>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <CBadge :color="getStockLevelColor(product.current_stock, product.min_quantity)">
                        {{ getStockLevelText(product.current_stock, product.min_quantity) }}
                    </CBadge>
                </div>
                <div>
                    <strong>{{ product.name }}</strong>
                    <div class="text-small text-muted">
                        SKU: {{ product.sku || 'N/A' }}
                    </div>
                </div>
            </div>
        </CTableDataCell>

        <CTableDataCell>
            {{ product.category?.name || 'Uncategorized' }}
        </CTableDataCell>

        <CTableDataCell>
            <div>
                <strong :class="getStockTextColor(product.current_stock, product.min_quantity)">
                    {{ product.current_stock }}
                </strong>
                <!-- <span class="text-muted"> {{ product.unit }}</span> -->
            </div>
        </CTableDataCell>

        <CTableDataCell>
            {{ product.min_limit }}
        </CTableDataCell>

        <CTableDataCell>
            <CProgress color="warning" :value="getStockPercentage(product.current_stock, product.min_limit)"
                      class="mb-1" style="height: 6px;" />
            <small class="text-warning">
                {{ getStockPercentage(product.current_stock, product.min_limit) }}% of minimum
            </small>
        </CTableDataCell>

        <CTableDataCell class="text-end">
            <CButtonGroup>
                <router-link :to="{ name: 'products.edit', params: { id: product.id } }">
                    <CButton size="sm" color="primary" title="Edit Product">
                        <CIcon :icon="cilPencil" />
                    </CButton>
                </router-link>
                <router-link :to="{ name: 'stocks.create', query: { product_id: product.id, type: 'purchase' } }">
                    <CButton size="sm" color="success" title="Restock">
                        <CIcon :icon="cilCart" />
                    </CButton>
                </router-link>
            </CButtonGroup>
        </CTableDataCell>
    </CTableRow>
                        </CTableBody>
                    </CTable>

                    <div class="mt-3 text-end">
                        <!-- <router-link :to="{ name: 'reports.low-stock' }"> -->
                            <CButton color="outline-warning" size="sm">
                                View All Low Stock Reports
                                <CIcon :icon="cilArrowRight" class="ms-1" />
                            </CButton>
                        <!-- </router-link> -->
                    </div>
                </div>
            </CCardBody>
        </CCard>

        <!-- Recent Activities -->
        <CRow>
            <CCol :md="6">
                <CCard class="h-100">
                    <CCardHeader>
                        <h5 class="mb-0">Recent Stock Activities</h5>
                    </CCardHeader>
                    <CCardBody>
                        <div v-if="loadingActivities" class="text-center py-4">
                            <CSpinner size="sm" />
                        </div>
                        <div v-else-if="recentActivities.length === 0" class="text-center py-4">
                            <p class="text-muted">No recent activities</p>
                        </div>
                        <div v-else class="activity-list">
                            <div v-for="activity in recentActivities" :key="activity.id"
                                 class="activity-item d-flex mb-3">
                                <div class="activity-icon me-3">
                                    <CBadge :color="getActivityColor(activity.type)" class="rounded-circle p-2">
                                        <CIcon :icon="getActivityIcon(activity.type)" />
                                    </CBadge>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ getActivityTitle(activity.type) }}</strong>
                                        <small class="text-muted">{{ formatTimeAgo(activity.created_at) }}</small>
                                    </div>
                                    <p class="mb-1 small">{{ activity.description }}</p>
                                    <!-- <small class="text-muted">By: {{ activity.user?.name || 'System' }}</small> -->
                                </div>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </CCol>

            <CCol :md="6">
                <CCard class="h-100">
                    <CCardHeader>
                        <h5 class="mb-0">Quick Actions</h5>
                    </CCardHeader>
                    <CCardBody>
                        <CRow class="g-3">
                            <CCol :md="6">
                                <CButton color="primary" class="w-100 h-100 py-4" :to="{ name: 'stocks.create', query: { type: 'sale' } }">
                                    <CIcon :icon="cilCash" size="xl" class="mb-2" />
                                    <div>New Sale</div>
                                </CButton>
                            </CCol>
                            <CCol :md="6">
                                <CButton color="success" class="w-100 h-100 py-4" :to="{ name: 'stocks.create', query: { type: 'purchase' } }">
                                    <CIcon :icon="cilCart" size="xl" class="mb-2" />
                                    <div>New Purchase</div>
                                </CButton>
                            </CCol>
                            <CCol :md="6">
                                <CButton color="info" class="w-100 h-100 py-4" :to="{ name: 'products.create' }">
                                    <CIcon :icon="cilPlus" size="xl" class="mb-2" />
                                    <div>Add Product</div>
                                </CButton>
                            </CCol>
                            <CCol :md="6">
                                <CButton color="warning" class="w-100 h-100 py-4" :to="{ name: 'reports.daily-summary' }">
                                    <CIcon :icon="cilChart" size="xl" class="mb-2" />
                                    <div>Daily Report</div>
                                </CButton>
                            </CCol>
                        </CRow>
                    </CCardBody>
                </CCard>
            </CCol>
        </CRow>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Chart from 'chart.js/auto'

// CoreUI Icons
import {
    cilChartLine,
    cilCalendar,
    cilClock,
    cilArrowTop,
    cilArrowBottom,
    cilWarning,
    cilChart,
    cilChartPie,
    cilCheckCircle,
    cilCart,
    cilPencil,
    cilArrowRight,
    cilCash,
    cilPlus,
    cilListRich,
    cilInbox,
    cilTags
} from '@coreui/icons'
import { CIcon } from '@coreui/icons-vue'

const router = useRouter()

// Data
const loading = ref(true)
const loadingChart = ref(false)
const loadingActivities = ref(false)
const name = ref('User')
const currentDate = ref('')
const currentTime = ref('')
const stats = ref({})
const profitData = ref([])
const stockOverview = ref([])
const lowStockProducts = ref([])
const recentActivities = ref([])

// Chart references
const profitChart = ref(null)
const stockChart = ref(null)
let profitChartInstance = null
let stockChartInstance = null

// Methods
const fetchDashboardData = async () => {
    loading.value = true
    loadingChart.value = true

    try {
        // Fetch dashboard stats
        const [statsRes, profitRes, stockRes, lowStockRes, activitiesRes] = await Promise.all([
            axios.get('/dashboard/stats'),
            axios.get('/dashboard/profit-trend'),
            axios.get('/dashboard/stock-overview'),
            axios.get('/dashboard/low-stock-products'),
            axios.get('/dashboard/recent-activities')
        ])
        console.log(statsRes, profitRes, stockRes, lowStockRes, activitiesRes)

        if (statsRes.data.success) stats.value = statsRes.data.data
        if (profitRes.data.success) profitData.value = profitRes.data.data
        if (stockRes.data.success) stockOverview.value = stockRes.data.data
        if (lowStockRes.data.success) lowStockProducts.value = lowStockRes.data.data
        if (activitiesRes.data.success) recentActivities.value = activitiesRes.data.data

        console.log(stats.value)


        // Set user name from stats or localStorage
        name.value = stats.value.user ||
                        localStorage.getItem('user_name') ||
                        'User'

    } catch (error) {
        console.error('Error fetching dashboard data:', error)
    } finally {
        loading.value = false
        loadingChart.value = false
        loadingActivities.value = false

        // Initialize charts after data is loaded
        setTimeout(initializeCharts, 100)
    }
}

const transactionDistribution = computed(() => {
    if (!recentActivities.value || recentActivities.value.length === 0) {
        return [];
    }

    // Group activities by type
    const typeCounts = {};
    const typeAmounts = {};

    // Colors for different transaction types
    const typeColors = {
        'sale': '#4CAF50',      // Green
        'purchase': '#2196F3',  // Blue
        'issue': '#FF9800',     // Orange
        'return': '#9C27B0'     // Purple
    };

    // Default color for unknown types
    const defaultColor = '#607D8B'; // Blue Grey

    // Count transactions by type
    recentActivities.value.forEach(activity => {
        const type = activity.type || 'unknown';

        if (!typeCounts[type]) {
            typeCounts[type] = 0;
            typeAmounts[type] = 0;
        }

        typeCounts[type]++;

        // Extract amount from description
        const description = activity.description || '';
        const amountMatch = description.match(/PKR\s*([\d,]+\.?\d*)/);
        if (amountMatch) {
            const amount = parseFloat(amountMatch[1].replace(/,/g, ''));
            typeAmounts[type] += amount;
        }
    });

    const totalCount = recentActivities.value.length;

    // Convert to array format
    return Object.keys(typeCounts).map(type => {
        const count = typeCounts[type];
        const totalAmount = typeAmounts[type];
        const percentage = Math.round((count / totalCount) * 100);

        return {
            type: type.charAt(0).toUpperCase() + type.slice(1), // Capitalize
            count: count,
            totalAmount: totalAmount,
            percentage: percentage,
            color: typeColors[type] || defaultColor
        };
    }).sort((a, b) => b.count - a.count); // Sort by count descending
});

// Add chart instance reference
let transactionChartInstance = null;
const transactionChart = ref(null);

    // Add this function after transactionDistribution computed property
const initializeTransactionChart = () => {
    if (transactionDistribution.value.length > 0 && transactionChart.value) {
        const ctx = transactionChart.value.getContext('2d');

        // Destroy previous chart instance if exists
        if (transactionChartInstance) {
            transactionChartInstance.destroy();
        }

        // Prepare chart data
        const chartData = {
            labels: transactionDistribution.value.map(item => item.type),
            datasets: [{
                data: transactionDistribution.value.map(item => item.count),
                backgroundColor: transactionDistribution.value.map(item => item.color),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 10
            }]
        };

        // Create chart
        transactionChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false, // This is important!
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const item = transactionDistribution.value[context.dataIndex];
                                const label = context.label || '';
                                const value = context.raw || 0;

                                return [
                                    `${label}: ${value} transactions`,
                                    `Total: PKR ${formatCurrency(item.totalAmount)}`,
                                    `${item.percentage}% of total`
                                ];
                            }
                        }
                    }
                },
                cutout: '65%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }
};
const initializeCharts = () => {
    // Destroy existing charts
    if (profitChartInstance) {
        profitChartInstance.destroy()
    }
    if (stockChartInstance) {
        stockChartInstance.destroy()
    }

    // Initialize Profit Chart
    if (profitData.value.length > 0 && profitChart.value) {
        const ctx = profitChart.value.getContext('2d')

        profitChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: profitData.value.map(item => item.date),
                datasets: [
                    {
                        label: 'Revenue',
                        data: profitData.value.map(item => item.revenue),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Cost',
                        data: profitData.value.map(item => item.cost),
                        borderColor: '#F44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Profit',
                        data: profitData.value.map(item => item.profit),
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: PKR ${formatCurrency(context.raw)}`
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'PKR ' + formatCurrency(value)
                            }
                        }
                    }
                }
            }
        })
          // Initialize Transaction Chart
    initializeTransactionChart();
    }

    // Initialize Stock Overview Chart
    if (stockOverview.value.length > 0 && stockChart.value) {
        const ctx = stockChart.value.getContext('2d')

        stockChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: stockOverview.value.map(item => item.category),
                datasets: [{
                    data: stockOverview.value.map(item => item.total_value),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: PKR ${formatCurrency(context.raw)}`
                            }
                        }
                    }
                }
            }
        })
    }

}

// Helper methods
const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatTimeAgo = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now - date
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`
    return formatDate(dateString)
}

const getStockLevelColor = (currentStock, minQuantity) => {
    const percentage = (currentStock / minQuantity) * 100
    if (percentage <= 20) return 'danger'
    if (percentage <= 50) return 'warning'
    return 'secondary'
}

const getStockLevelText = (currentStock, minQuantity) => {
    const percentage = (currentStock / minQuantity) * 100
    if (percentage <= 20) return 'Critical'
    if (percentage <= 50) return 'Low'
    return 'Warning'
}

const getStockTextColor = (currentStock, minQuantity) => {
    const percentage = (currentStock / minQuantity) * 100
    if (percentage <= 20) return 'text-danger'
    if (percentage <= 50) return 'text-warning'
    return 'text-secondary'
}

const getStockPercentage = (currentStock, minQuantity) => {
    if (minQuantity === 0) return 100
    return Math.min(100, Math.round((currentStock / minQuantity) * 100))
}


const getActivityColor = (type) => {
    const colors = {
        sale: 'success',
        purchase: 'info',
        issue: 'warning',
        return: 'secondary',
        stock_adjustment: 'primary'
    }
    return colors[type] || 'light'
}

const getActivityIcon = (type) => {
    const icons = {
        sale: cilCash,
        purchase: cilCart,
        issue: cilInbox,
        return: cilArrowRight,
        stock_adjustment: cilTags
    }
    return icons[type] || cilListRich
}

const getActivityTitle = (type) => {
    const titles = {
        sale: 'Sale Transaction',
        purchase: 'Purchase Order',
        issue: 'Stock Issued',
        return: 'Return Processed',
        stock_adjustment: 'Stock Adjusted'
    }
    return titles[type] || 'Stock Activity'
}

// Update time every minute
const updateDateTime = () => {
    const now = new Date()
    currentDate.value = now.toLocaleDateString('en-PK', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
    currentTime.value = now.toLocaleTimeString('en-PK', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Lifecycle
onMounted(() => {
    updateDateTime()
    fetchDashboardData()

    // Update time every minute
    const timer = setInterval(updateDateTime, 60000)

    // Refresh data every 5 minutes
    const refreshTimer = setInterval(fetchDashboardData, 300000)

    // Cleanup on unmount
    onUnmounted(() => {
        clearInterval(timer)
        clearInterval(refreshTimer)
        if (profitChartInstance) profitChartInstance.destroy()
        if (stockChartInstance) stockChartInstance.destroy()
    })
})
</script>

<style scoped>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.activity-item {
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-btn {
    height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.text-small {
    font-size: 0.875rem;
}

.chart-container {
    position: relative;
    height: 250px;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

@media (max-width: 768px) {
    .chart-container {
        height: 200px;
    }
}
</style>
