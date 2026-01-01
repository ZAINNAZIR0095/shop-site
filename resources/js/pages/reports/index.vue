<template>
    <div>
        <!-- Header Card with Summary -->
        <CCard class="mb-4">
            <CCardBody>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <!-- Title with Stats -->
                    <div>
                        <h1 class="text-h5 mb-2">Stock Management Report</h1>
                        <div class="d-flex flex-wrap gap-3">
                            <!-- Total Products -->
                            <div class="summary-card">
                                <small class="text-muted">Total Products</small>
                                <h3 class="mb-0">{{ formatNumber(summary.total_products || 0) }}</h3>
                            </div>

                            <!-- Total Stock Value -->
                            <div class="summary-card">
                                <small class="text-muted">Total Stock Value</small>
                                <h3 class="mb-0">Rs. {{ formatCurrency(summary.total_stock_value || 0) }}</h3>
                            </div>

                            <!-- Low Stock Items -->
                            <div class="summary-card">
                                <small class="text-muted">Low Stock Items</small>
                                <h3 class="mb-0 text-warning">{{ formatNumber(summary.low_stock_items || 0) }}</h3>
                            </div>

                            <!-- Out of Stock -->
                            <div class="summary-card">
                                <small class="text-muted">Out of Stock</small>
                                <h3 class="mb-0 text-danger">{{ formatNumber(summary.out_of_stock_items || 0) }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <!-- Export Buttons -->
                    <!-- <div class="d-flex flex-wrap gap-2">
                        <CButton color="success" @click="exportToExcel">
                            <CIcon name="cil-cloud-download" class="me-2" />
                            Export Excel
                        </CButton>
                        <CButton color="info" @click="printReport">
                            <CIcon name="cil-print" class="me-2" />
                            Print Report
                        </CButton>
                    </div> -->
                </div>
            </CCardBody>
        </CCard>

        <!-- Filters Card -->
        <CCard class="mb-4">
            <CCardBody>
                <CRow class="g-3">
                    <!-- Search by Name -->
                    <CCol :md="4">
                        <CFormInput
                            v-model="filters.search"
                            placeholder="Search by product name..."
                            @input="handleSearch"
                        />
                    </CCol>

                    <!-- Stock Status Filter -->
                    <CCol :md="3">
                        <CFormSelect v-model="filters.stock_status" @change="filterByStockStatus">
                            <option value="">All Stock Status</option>
                            <option value="all">All Products</option>
                            <option value="low">Low Stock Only</option>
                            <option value="out">Out of Stock Only</option>
                            <option value="sufficient">Sufficient Stock Only</option>
                        </CFormSelect>
                    </CCol>

                    <!-- Min Limit Filter -->
                    <CCol :md="3">
                        <CFormInput
                            v-model="filters.min_limit"
                            type="number"
                            min="0"
                            placeholder="Min Limit (≥ value)"
                            @input="handleMinLimitChange"
                        />
                    </CCol>

                    <!-- Sort By -->
                    <CCol :md="2">
                        <CFormSelect v-model="filters.sort_by" @change="fetchStockReport">
                            <option value="">Sort By</option>
                            <option value="name">Name A-Z</option>
                            <option value="-name">Name Z-A</option>
                            <option value="current_stock">Stock Low-High</option>
                            <option value="-current_stock">Stock High-Low</option>
                            <option value="stock_value">Value Low-High</option>
                            <option value="-stock_value">Value High-Low</option>
                            <option value="purchase_price">Price Low-High</option>
                            <option value="-purchase_price">Price High-Low</option>
                        </CFormSelect>
                    </CCol>
                </CRow>
            </CCardBody>
        </CCard>

        <!-- Stock Report Table -->
        <CCard>
            <CCardBody class="p-0">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <CSpinner />
                    <p class="mt-2">Loading stock report...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="products.length === 0" class="text-center py-5">
                    <CIcon name="cil-inbox" size="3xl" class="text-muted mb-3" />
                    <h5 class="text-h5">No Stock Records Found</h5>
                    <p class="text-body-1 text-medium-emphasis">
                        No products match your current filters
                    </p>
                    <CButton color="primary" @click="resetFilters" class="mt-3">
                        Reset Filters
                    </CButton>
                </div>

                <!-- Stock Table -->
                <div v-else>
                    <div class="table-responsive">
                        <CTable hover class="stock-table mb-0">
                            <CTableHead class="table-light">
                                <CTableRow>
                                    <CTableHeaderCell width="50">#</CTableHeaderCell>
                                    <CTableHeaderCell>Product</CTableHeaderCell>
                                    <CTableHeaderCell>Type</CTableHeaderCell>
                                    <CTableHeaderCell>Unit</CTableHeaderCell>
                                    <CTableHeaderCell class="text-center">Current Stock</CTableHeaderCell>
                                    <CTableHeaderCell class="text-center">Min Limit</CTableHeaderCell>
                                    <CTableHeaderCell class="text-center">Status</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Purchase Price</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Sale Price</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Stock Value</CTableHeaderCell>
                                </CTableRow>
                            </CTableHead>
                            <CTableBody>
                                <CTableRow
                                    v-for="(product, index) in paginatedFilteredProducts"
                                    :key="product.id"
                                    :class="getStockRowClass(product)"
                                >
                                    <!-- Serial Number -->
                                    <CTableDataCell class="fw-bold">
                                        {{ pagination.from + index }}
                                    </CTableDataCell>

                                    <!-- Product Information -->
                                    <CTableDataCell>
                                        <div class="d-flex flex-column">
                                            <strong class="mb-1">{{ product.name }}</strong>
                                            <div v-if="product.size" class="text-small text-muted">
                                                <small>Size: {{ product.size }}</small>
                                            </div>
                                            <small class="text-muted">ID: {{ product.id }}</small>
                                        </div>
                                    </CTableDataCell>

                                    <!-- Type -->
                                    <CTableDataCell>
                                        <CBadge :color="product.type === 'physical' ? 'primary' : 'info'">
                                            {{ product.type || '-' }}
                                        </CBadge>
                                    </CTableDataCell>

                                    <!-- Unit -->
                                    <CTableDataCell>
                                        <span class="badge bg-secondary">{{ product.unit || '-' }}</span>
                                    </CTableDataCell>

                                    <!-- Current Stock -->
                                    <CTableDataCell class="text-center">
                                        <div :class="getStockTextClass(product)">
                                            <strong>{{ formatNumber(product.current_stock) }}</strong>
                                        </div>
                                        <div v-if="isNegativeStock(product)" class="text-danger small">
                                            (Negative Stock)
                                        </div>
                                    </CTableDataCell>

                                    <!-- Min Limit -->
                                    <CTableDataCell class="text-center">
                                        <div class="fw-semibold">{{ formatNumber(product.min_limit) }}</div>
                                        <div class="small text-muted">
                                            {{ getStockPercentage(product) }}
                                        </div>
                                    </CTableDataCell>

                                    <!-- Status -->
                                    <CTableDataCell class="text-center">
                                        <CBadge :color="getStockStatusColor(product)" class="status-badge">
                                            {{ getStockStatus(product) }}
                                        </CBadge>
                                        <div v-if="product.is_low_stock && product.current_stock > 0" class="small text-warning mt-1">
                                            {{ getRemainingStock(product) }} left
                                        </div>
                                    </CTableDataCell>

                                    <!-- Purchase Price -->
                                    <CTableDataCell class="text-end">
                                        <div class="fw-bold">Rs. {{ formatCurrency(product.purchase_price) }}</div>
                                    </CTableDataCell>

                                    <!-- Sale Price -->
                                    <CTableDataCell class="text-end">
                                        <div class="fw-bold text-success">Rs. {{ formatCurrency(product.sale_price) }}</div>
                                        <div class="small text-muted">
                                            Margin: {{ calculateMargin(product) }}
                                        </div>
                                    </CTableDataCell>

                                    <!-- Stock Value -->
                                    <CTableDataCell class="text-end">
                                        <div class="fw-bold text-primary">
                                            Rs. {{ formatCurrency(product.stock_value) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ calculateStockValuePercentage(product) }} of total
                                        </div>
                                    </CTableDataCell>
                                </CTableRow>
                            </CTableBody>
                        </CTable>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div class="text-muted">
                            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of
                            {{ formatNumber(pagination.total || 0) }} products
                        </div>

                        <!-- Items per page -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">Items per page:</span>
                            <CFormSelect v-model="filters.per_page" @change="handlePerPageChange" style="width: auto;">
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </CFormSelect>
                        </div>

                        <!-- Pagination buttons -->
                        <CPagination v-if="pagination.last_page > 1">
                            <CPaginationItem
                                :disabled="pagination.current_page === 1"
                                @click="changePage(pagination.current_page - 1)"
                                class="page-item"
                            >
                                <span class="page-link">Previous</span>
                            </CPaginationItem>

                            <CPaginationItem
                                v-for="page in visiblePages"
                                :key="page"
                                :active="page === pagination.current_page"
                                @click="changePage(page)"
                                class="page-item"
                            >
                                <span class="page-link">{{ page }}</span>
                            </CPaginationItem>

                            <CPaginationItem
                                :disabled="pagination.current_page === pagination.last_page"
                                @click="changePage(pagination.current_page + 1)"
                                class="page-item"
                            >
                                <span class="page-link">Next</span>
                            </CPaginationItem>
                        </CPagination>
                    </div>
                </div>
            </CCardBody>
        </CCard>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

const route = useRoute()
const router = useRouter()

// Data
const products = ref([])
const loading = ref(true)
const summary = ref({})
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0,
    total: 0,
    per_page: 15
})

// Filters
const filters = reactive({
    search: route.query.search || '',
    stock_status: route.query.stock_status || '',
    min_limit: route.query.min_limit || '',
    sort_by: route.query.sort_by || '',
    page: route.query.page || 1,
    per_page: route.query.per_page || 15
})

// Update URL when filters change
const updateURL = () => {
    const query = { ...filters }

    // Remove empty values
    Object.keys(query).forEach(key => {
        if (query[key] === '' || query[key] === null || query[key] === undefined) {
            delete query[key]
        }
    })

    router.replace({ query })
}

// Fetch stock report
const fetchStockReport = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value)
            }
        })

        const response = await axios.get(`/stocks-reports?${params.toString()}`)
        console.log(response.data)

        if (response.data.success) {
            products.value = response.data.data.products || []
            summary.value = response.data.data.summary || {}

            // Update pagination
            if (response.data.data.pagination) {
                Object.assign(pagination, response.data.data.pagination)
            }
        }
    } catch (error) {
        console.error('Error fetching stock report:', error)
    } finally {
        loading.value = false
    }
}

const paginatedFilteredProducts = computed(() => {
    const filtered = filteredProducts.value;
    // const start = (currentPage.value - 1) * itemsPerPage.value;
    // const end = start + itemsPerPage.value;
    return filtered
});

// Filter function for stock status
const filterByStockStatus = () => {
    if (!filters.stock_status || filters.stock_status === 'all') {
        return products.value; // Show all products
    }

    return products.value.filter(product => {
        const currentStock = Number(product.current_stock) || 0;
        const minLimit = Number(product.min_limit) || 0;

        switch (filters.stock_status) {
            case 'low':
                // Low stock: current stock <= min_limit AND current stock > 0
                return currentStock > 0 && currentStock <= minLimit;

            case 'out':
                // Out of stock: current stock <= 0
                return currentStock <= 0;

            case 'sufficient':
                // Sufficient stock: current stock > min_limit
                return currentStock > minLimit;

            default:
                return true;
        }
    });
};


// Computed property for filtered products
const filteredProducts = computed(() => {
    let result = [...products.value];

    // Apply search filter
    if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        result = result.filter(product =>
            product.name.toLowerCase().includes(searchTerm) ||
            (product.size && product.size.toLowerCase().includes(searchTerm)) ||
            product.type.toLowerCase().includes(searchTerm)
        );
    }

    // Apply min limit filter
    if (filters.min_limit && !isNaN(filters.min_limit)) {
        const minLimitValue = Number(filters.min_limit);
        result = result.filter(product =>
            Number(product.min_limit) >= minLimitValue
        );
    }

    // Apply stock status filter
    if (filters.stock_status && filters.stock_status !== 'all') {
        result = result.filter(product => {
            const currentStock = Number(product.current_stock) || 0;
            const minLimit = Number(product.min_limit) || 0;

            switch (filters.stock_status) {
                case 'low':
                    // Low stock: current stock <= min_limit AND current stock > 0
                    return currentStock > 0 && currentStock <= minLimit;

                case 'out':
                    // Out of stock: current stock <= 0
                    return currentStock <= 0;

                case 'sufficient':
                    // Sufficient stock: current stock > min_limit
                    return currentStock > minLimit;

                default:
                    return true;
            }
        });
    }

    // Apply sorting
    if (filters.sort_by) {
        result.sort((a, b) => {
            let field = filters.sort_by;
            let order = 1;

            if (field.startsWith('-')) {
                field = field.substring(1);
                order = -1;
            }

            // Handle different field types
            switch (field) {
                case 'name':
                    return order * a.name.localeCompare(b.name);

                case 'current_stock':
                    return order * (Number(a.current_stock) - Number(b.current_stock));

                case 'stock_value':
                    return order * (Number(a.stock_value) - Number(b.stock_value));

                case 'purchase_price':
                    return order * (Number(a.purchase_price) - Number(b.purchase_price));

                case 'sale_price':
                    return order * (Number(a.sale_price) - Number(b.sale_price));

                case 'min_limit':
                    return order * (Number(a.min_limit) - Number(b.min_limit));

                default:
                    return 0;
            }
        });
    }

    return result;
});

console.log(filterByStockStatus)

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

// Stock analysis functions
const getStockStatus = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0

    if (stock <= 0) return 'Out of Stock'
    if (stock <= minLimit) return 'Low Stock'
    return 'In Stock'
}

const getStockStatusColor = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0

    if (stock <= 0) return 'danger'
    if (stock <= minLimit) return 'warning'
    return 'success'
}

const getStockTextClass = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0

    if (stock <= 0) return 'text-danger'
    if (stock <= minLimit) return 'text-warning'
    return 'text-success'
}

const getStockRowClass = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0

    if (stock <= 0) return 'table-danger'
    if (stock <= minLimit) return 'table-warning'
    return ''
}

const isNegativeStock = (product) => {
    return Number(product.current_stock) < 0
}

const getStockPercentage = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0

    if (minLimit === 0) return '-'

    const percentage = Math.round((stock / minLimit) * 100)
    return `${percentage}% of min`
}

const getRemainingStock = (product) => {
    const stock = Number(product.current_stock) || 0
    const minLimit = Number(product.min_limit) || 0
    const remaining = Math.max(0, minLimit - stock)
    return formatNumber(remaining)
}

const calculateMargin = (product) => {
    const purchase = Number(product.purchase_price) || 0
    const sale = Number(product.sale_price) || 0

    if (purchase === 0) return '-'

    const margin = ((sale - purchase) / purchase) * 100
    return `${margin.toFixed(1)}%`
}

const calculateStockValuePercentage = (product) => {
    const productValue = Number(product.stock_value) || 0
    const totalValue = Number(summary.value.total_stock_value) || 0

    if (totalValue === 0) return '0%'

    const percentage = (productValue / totalValue) * 100
    return `${percentage.toFixed(1)}%`
}

// Pagination
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
    filters.page = 1
    updateURL()
    fetchStockReport()
}, 500)

const handleMinLimitChange = debounce(() => {
    filters.page = 1
    updateURL()
    fetchStockReport()
}, 500)

const handlePerPageChange = () => {
    filters.page = 1
    updateURL()
    fetchStockReport()
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        filters.page = page
        updateURL()
        fetchStockReport()
    }
}

const resetFilters = () => {
    Object.keys(filters).forEach(key => {
        if (key !== 'per_page') {
            filters[key] = ''
        }
    })
    filters.page = 1
    updateURL()
    fetchStockReport()
}

const exportToExcel = async () => {
    try {
        const params = new URLSearchParams()

        Object.entries(filters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value)
            }
        })

        params.append('export', 'excel')

        window.open(`/stocks-reports/export?${params.toString()}`, '_blank')
    } catch (error) {
        console.error('Error exporting to Excel:', error)
    }
}

const printReport = () => {
    window.print()
}

// Watch route changes
watch(() => route.query, (newQuery) => {
    Object.keys(filters).forEach(key => {
        if (newQuery[key] !== undefined) {
            filters[key] = newQuery[key]
        } else if (key !== 'per_page') {
            filters[key] = ''
        }
    })

    filters.page = parseInt(filters.page) || 1
    fetchStockReport()
}, { immediate: true })

onMounted(() => {
    if (Object.keys(route.query).length > 0) {
        fetchStockReport()
    }
})
</script>

<style scoped>
.summary-card {
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    min-width: 140px;
}

.summary-card small {
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge {
    min-width: 90px;
    padding: 6px 12px;
    font-size: 0.85rem;
}

.stock-table th {
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #dee2e6;
}

.stock-table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.05) !important;
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.05) !important;
}

.text-small {
    font-size: 0.8rem;
}

.page-link {
    cursor: pointer;
}

@media print {
    .d-print-none {
        display: none !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
