<template>
    <div>
        <!-- Header Card with Summary -->
        <CCard class="mb-4">
            <CCardBody>
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4">
                    <!-- Title with Stats -->
                    <div>
                        <h1 class="text-h4 mb-3">Sales & Purchase Report</h1>
                        <CRow class="g-3">
                            <!-- Total Sales -->
                            <CCol :sm="6" :lg="4">
                                <div
                                    class="summary-card bg-success bg-opacity-10 border border-success border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <small class="text-muted">Total Sales</small>
                                            <h3 class="mb-0">Rs. {{ formatCurrency(summary.total_sales || 0) }}</h3>
                                            <small class="text-success">
                                                {{ formatNumber(summary.total_sales_count || 0) }} transactions
                                            </small>
                                        </div>
                                        <CIcon :icon="cilCart" class="text-success" size="2xl" />
                                    </div>
                                </div>
                            </CCol>

                            <!-- Total Purchases -->
                            <CCol :sm="6" :lg="4">
                                <div class="summary-card bg-info bg-opacity-10 border border-info border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <small class="text-muted">Total Purchases</small>
                                            <h3 class="mb-0">Rs. {{ formatCurrency(summary.total_purchases || 0) }}</h3>
                                            <small class="text-info">
                                                {{ formatNumber(summary.total_purchases_count || 0) }} transactions
                                            </small>
                                        </div>
                                        <CIcon :icon="cilBasket" class="text-info" size="2xl" />
                                    </div>
                                </div>
                            </CCol>

                            <!-- Top Selling Product -->
                            <CCol :sm="6" :lg="4">
                                <div
                                    class="summary-card bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <small class="text-muted">Top Selling Product</small>
                                            <h6 class="mb-1 text-truncate" style="max-width: 150px;">
                                                {{ summary.top_selling_product?.name || 'N/A' }}
                                            </h6>
                                            <small class="text-warning">
                                                Sold: {{ formatNumber(summary.top_selling_product?.total_sold || 0) }}
                                            </small>
                                        </div>
                                        <CIcon :icon="cilStar" class="text-warning" size="2xl" />
                                    </div>
                                </div>
                            </CCol>
                        </CRow>
                    </div>

                    <!-- Date Range Picker - Fixed Version -->
                    <div class="date-range-picker d-flex flex-column align-items-start align-items-md-end gap-3">
                        <!-- Date Inputs Row -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <CIcon :icon="cilCalendar" class="text-muted flex-shrink-0" />
                            <CFormInput type="date" v-model="filters.start_date" @change="handleDateChange"
                                :max="filters.end_date" class="w-auto" />

                            <span class="text-muted flex-shrink-0">to</span>
                            <CFormInput type="date" v-model="filters.end_date" @change="handleDateChange"
                                :min="filters.start_date" class="w-auto" />
                        </div>

                        <!-- Quick Range Buttons -->
                        <div class="d-flex gap-2 flex-wrap">
                            <CButton color="light" size="sm" @click="setDateRange('today')">Today</CButton>
                            <CButton color="light" size="sm" @click="setDateRange('week')">This Week</CButton>
                            <CButton color="light" size="sm" @click="setDateRange('month')">This Month</CButton>
                        </div>
                    </div>
                </div>
            </CCardBody>
        </CCard>



        <!-- Filters Card -->
        <CCard class="mb-4">
            <CCardBody>
                <CRow class="g-3">

                    <!-- Search -->
                    <CCol :md="4">
                        <CFormInput v-model="filters.search" placeholder="Search by product/customer..."
                            @input="handleSearch" />
                    </CCol>

                    <!-- Transaction Type -->
                    <CCol :md="3">
                        <CFormSelect v-model="filters.transaction_type">
                            <option value="">All Transactions</option>
                            <option value="sale">Sales Only</option>
                            <option value="purchase">Purchases Only</option>
                        </CFormSelect>
                    </CCol>

                    <!-- Sort By -->
                    <CCol :md="3">
                        <CFormSelect v-model="filters.sort">
                            <option value="-date">Date (Newest First)</option>
                            <option value="date">Date (Oldest First)</option>
                            <option value="-total_amount">Amount (High to Low)</option>
                            <option value="total_amount">Amount (Low to High)</option>
                        </CFormSelect>
                    </CCol>
                    <!-- Sort By -->
                    <CCol :md="3">
                        <CButton color="primary" @click="resetFilters" class="mt-3">
                            Reset Filters
                        </CButton>
                    </CCol>
                </CRow>
            </CCardBody>
        </CCard>

        <!-- Transactions Table -->
        <CCard>
            <CCardBody class="p-0">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <CSpinner />
                    <p class="mt-2">Loading report...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="transactions.length === 0" class="text-center py-5">
                    <CIcon :icon="cilInbox" size="3xl" class="text-muted mb-3" />
                    <h5 class="text-h5">No Transactions Found</h5>
                    <p class="text-body-1 text-medium-emphasis">
                        No transactions match your current filters
                    </p>
                    <CButton color="primary" @click="resetFilters" class="mt-3">
                        Reset Filters
                    </CButton>
                </div>

                <!-- Transactions Table -->
                <div v-else>
                    <div class="table-responsive">
                        <CTable hover class="transactions-table mb-0">
                            <CTableHead class="table-light">
                                <CTableRow>
                                    <CTableHeaderCell width="50">#invoice</CTableHeaderCell>
                                    <CTableHeaderCell>Type</CTableHeaderCell>
                                    <CTableHeaderCell>Date</CTableHeaderCell>
                                    <CTableHeaderCell>Product/Customer</CTableHeaderCell>
                                    <CTableHeaderCell class="text-center">Quantity</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Unit Price</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Amount</CTableHeaderCell>
                                </CTableRow>
                            </CTableHead>
                            <CTableBody>
                                <CTableRow v-for="(transaction, index) in paginatedTransactions" :key="transaction.id"
                                    :class="getRowClass(transaction)">
                                    <!-- Serial Number -->
                                    <CTableDataCell class="fw-bold">
                                        {{ transaction.id }}
                                    </CTableDataCell>

                                    <!-- Type -->
                                    <CTableDataCell>
                                        <CBadge :color="transaction.type === 'sale' ? 'success' : 'info'">
                                            {{ transaction.type === 'sale' ? 'Sale' : 'Purchase' }}
                                        </CBadge>
                                    </CTableDataCell>

                                    <!-- Date -->
                                    <CTableDataCell>
                                        <div>{{ formatDate(transaction.date) }}</div>
                                        <small class="text-muted">{{ formatTime(transaction.created_at) }}</small>
                                    </CTableDataCell>

                                    <!-- Product/Customer -->
                                    <CTableDataCell>
                                        <div class="d-flex flex-column">
                                            <strong class="mb-1">{{ transaction.product_name }}</strong>
                                            <small class="text-muted">
                                                {{ transaction.type === 'sale' ? 'Customer: ' : 'Supplier: ' }}
                                                {{ transaction.customer_name || transaction.supplier_name || 'N/A' }}
                                            </small>
                                        </div>
                                    </CTableDataCell>

                                    <!-- Quantity -->
                                    <CTableDataCell class="text-center">
                                        <div class="fw-bold">{{ formatNumber(transaction.quantity) }}</div>
                                        <small class="text-muted">{{ transaction.unit || '-' }}</small>
                                    </CTableDataCell>

                                    <!-- Unit Price -->
                                    <CTableDataCell class="text-end">
                                        <div class="fw-bold">Rs. {{ formatCurrency(transaction.unit_price) }}</div>
                                        <small class="text-muted">
                                            {{ transaction.type === 'sale' ? 'Sale' : 'Purchase' }} Price
                                        </small>
                                    </CTableDataCell>

                                    <!-- Amount -->
                                    <CTableDataCell class="text-end">
                                        <div class="fw-bold"
                                            :class="transaction.type === 'sale' ? 'text-success' : 'text-info'">
                                            Rs. {{ formatCurrency(transaction.total_amount) }}
                                        </div>
                                        <small class="text-muted">Total</small>
                                    </CTableDataCell>

                                </CTableRow>
                            </CTableBody>
                        </CTable>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted">
                            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of
                            {{ formatNumber(pagination.total || 0) }} transactions
                        </div>

                        <!-- Items per page -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">Items per page:</span>
                            <CFormSelect v-model.number="filters.limit" @change="handlePerPageChange"
                                style="width: auto;">
                                <option :value="5">5</option>
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </CFormSelect>
                        </div>

                        <!-- Pagination buttons -->
                        <CPagination v-if="pagination.last_page > 1">
                            <CPaginationItem :disabled="pagination.current_page === 1"
                                @click="changePage(pagination.current_page - 1)">
                                Previous
                            </CPaginationItem>
                            <CPaginationItem v-for="page in visiblePages" :key="page"
                                :active="page === pagination.current_page" @click="changePage(page)">
                                {{ page }}
                            </CPaginationItem>
                            <CPaginationItem :disabled="pagination.current_page === pagination.last_page"
                                @click="changePage(pagination.current_page + 1)">
                                Next
                            </CPaginationItem>
                        </CPagination>
                    </div>
                </div>
            </CCardBody>
        </CCard>

    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

const router = useRouter()

// Data
const transactions = ref([])
const loading = ref(true)
const summary = ref({})
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 25
})

// Filters - Fixed parameter names (common backend expectations)
const filters = reactive({
    transaction_type: '', // Changed from 'type'
    search: '',
    start_date: getDefaultStartDate(),
    end_date: getDefaultEndDate(),
    sort: '-date', // Changed from 'sort_by'
    page: 1,
    limit: 25 // Changed from 'per_page'
})

// Debug function
const debugLog = (label, data) => {
    console.log(`🔍 ${label}:`, data)
}

// Default date functions
function getDefaultStartDate() {
    const date = new Date()
    date.setDate(date.getDate() - 30)
    return date.toISOString().split('T')[0]
}

function getDefaultEndDate() {
    return new Date().toISOString().split('T')[0]
}

// Computed property for client-side filtering
const filteredTransactions = computed(() => {
    let result = [...transactions.value]

    // Filter by transaction type
    if (filters.transaction_type) {
        result = result.filter(t => t.type === filters.transaction_type)
    }

    // Filter by search
    if (filters.search) {
        const searchLower = filters.search.toLowerCase()
        result = result.filter(t => {
            return (
                (t.invoice_number || '').toLowerCase().includes(searchLower) ||
                (t.reference_number || '').toLowerCase().includes(searchLower) ||
                (t.product_name || '').toLowerCase().includes(searchLower) ||
                (t.customer_name || '').toLowerCase().includes(searchLower) ||
                (t.supplier_name || '').toLowerCase().includes(searchLower)
            )
        })
    }

    // Sort the results
    if (filters.sort) {
        result = [...result].sort((a, b) => {
            switch (filters.sort) {
                case '-date':
                    return new Date(b.date) - new Date(a.date)
                case 'date':
                    return new Date(a.date) - new Date(b.date)
                case '-total_amount':
                    return (b.total_amount || 0) - (a.total_amount || 0)
                case 'total_amount':
                    return (a.total_amount || 0) - (b.total_amount || 0)
                default:
                    return 0
            }
        })
    }

    return result
})

// Build axios request parameters - FIXED VERSION
const buildRequestParams = () => {
    const params = {
        start_date: filters.start_date || getDefaultStartDate(),
        end_date: filters.end_date || getDefaultEndDate(),
        page: filters.page || 1,
        limit: filters.limit || 25,
    }

    // Add conditional filters
    if (filters.transaction_type) {
        params.transaction_type = filters.transaction_type
    }

    if (filters.search) {
        params.search = filters.search
    }

    if (filters.sort) {
        params.sort = filters.sort
    }

    debugLog('Request Parameters', params)
    return params
}

// Fetch report data - ENHANCED VERSION
const fetchReport = async () => {
    loading.value = true
    debugLog('Fetching report with filters', filters)

    try {
        const params = buildRequestParams()

        debugLog('API Call', {
            url: '/transactions/report', // Changed to common API path
            params: params
        })

        const response = await axios.get('/transactions/report', { params })
        debugLog('API Response', response.data)

        if (response.data.success) {
            // Handle transactions
            transactions.value = response.data.data?.transactions || []
            debugLog('Transactions loaded', transactions.value.length)

            // Handle summary
            summary.value = response.data.data?.summary || {}
            debugLog('Summary loaded', summary.value)

            // Update pagination
            if (response.data.data?.pagination) {
                const paginationData = response.data.data.pagination
                Object.assign(pagination, {
                    current_page: parseInt(paginationData.current_page) || 1,
                    last_page: parseInt(paginationData.last_page) || 1,
                    from: parseInt(paginationData.from) || 0,
                    to: parseInt(paginationData.to) || 0,
                    total: parseInt(paginationData.total) || 0,
                    per_page: parseInt(paginationData.per_page) || filters.limit
                })
                debugLog('Pagination updated', pagination)
            } else {
                // Calculate local pagination
                const total = transactions.value.length
                const perPage = filters.limit
                const currentPage = filters.page
                const lastPage = Math.ceil(total / perPage)
                const from = total > 0 ? ((currentPage - 1) * perPage) + 1 : 0
                const to = Math.min(currentPage * perPage, total)

                Object.assign(pagination, {
                    current_page: currentPage,
                    last_page: lastPage,
                    from: from,
                    to: to,
                    total: total,
                    per_page: perPage
                })
            }
        } else {
            console.error('API error:', response.data.message || 'Unknown error')
            resetData()
        }
    } catch (error) {
        console.error('Network error:', error)
        if (error.response?.status === 401) {
            router.push('/login')
            return
        }
        resetData()
    } finally {
        loading.value = false
    }
}

// Reset data function
const resetData = () => {
    transactions.value = []
    summary.value = {}
    resetPagination()
}

// Reset pagination
const resetPagination = () => {
    Object.assign(pagination, {
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0,
        per_page: filters.limit
    })
}

// Formatting functions
const formatCurrency = (value) => {
    const num = Number(value) || 0
    return num.toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatNumber = (value) => {
    const num = Number(value) || 0
    return num.toLocaleString('en-PK')
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    try {
        return new Date(dateString).toLocaleDateString('en-PK', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        })
    } catch {
        return dateString
    }
}

const formatTime = (dateString) => {
    if (!dateString) return '-'
    try {
        return new Date(dateString).toLocaleTimeString('en-PK', {
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch {
        return '-'
    }
}

const paginatedTransactions = computed(() => {
    const start = (pagination.current_page - 1) * filters.limit
    const end = start + filters.limit

    // Update pagination totals based on filtered results
    const total = filteredTransactions.value.length
    pagination.total = total
    pagination.from = total > 0 ? start + 1 : 0
    pagination.to = Math.min(end, total)
    pagination.last_page = Math.ceil(total / filters.limit)

    return filteredTransactions.value.slice(start, end)
})

const visiblePages = computed(() => {
    const pages = []
    const maxVisible = 5
    let start = Math.max(1, pagination.current_page - Math.floor(maxVisible / 2))
    let end = Math.min(pagination.last_page, start + maxVisible - 1)

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }
    return pages
})

// Event handlers
const handleSearch = debounce(() => {
    pagination.current_page = 1
    filters.page = 1
    fetchReport()
}, 500)

const handleDateChange = debounce(() => {
    filters.page = 1
    pagination.current_page = 1
    fetchReport()  // ✅ This one SHOULD call API
}, 300)

const handlePerPageChange = () => {
    pagination.current_page = 1  // ✅ Reset to page 1
    filters.page = 1
    filters.limit = parseInt(filters.limit)
    pagination.per_page = filters.limit
}


const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        pagination.current_page = page  // ✅ Update pagination state
        filters.page = page             // ✅ Keep filter in sync
        // ❌ Remove fetchReport() - no API call needed
        // window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const resetFilters = () => {
    filters.transaction_type = ''
    filters.search = ''
    filters.start_date = getDefaultStartDate()
    filters.end_date = getDefaultEndDate()
    filters.page = 1
    filters.sort = '-date'
    fetchReport()
}

const setDateRange = (range) => {
    const today = new Date()
    const start = new Date()

    switch (range) {
        case 'today':
            filters.start_date = today.toISOString().split('T')[0]
            filters.end_date = today.toISOString().split('T')[0]
            break
        case 'week':
            start.setDate(today.getDate() - 7)
            filters.start_date = start.toISOString().split('T')[0]
            filters.end_date = today.toISOString().split('T')[0]
            break
        case 'month':
            start.setMonth(today.getMonth() - 1)
            filters.start_date = start.toISOString().split('T')[0]
            filters.end_date = today.toISOString().split('T')[0]
            break
    }

    filters.page = 1
    fetchReport()
}

// Update template bindings to match new filter names
const getRowClass = (transaction) => {
    if (transaction.payment_status === 'pending') return 'table-warning'
    if (transaction.payment_status === 'partial') return 'table-info'
    return ''
}

const getPaymentStatusColor = (transaction) => {
    switch (transaction.payment_status?.toLowerCase()) {
        case 'paid': return 'success'
        case 'pending': return 'warning'
        case 'partial': return 'info'
        default: return 'secondary'
    }
}



// Lifecycle hooks
onMounted(() => {
    fetchReport()
})
</script>

<style scoped>
/* Keep all your existing CSS styles - they remain the same */
.summary-card {
    padding: 15px;
    border-radius: 10px;
    height: 100%;
}

.date-range-picker {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    min-width: 300px;
}

.transactions-table th {
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #dee2e6;
}

.transactions-table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.05) !important;
}

.table-info {
    background-color: rgba(23, 162, 184, 0.05) !important;
}

@media print {
    .d-print-none {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .btn {
        display: none !important;
    }
}
</style>
