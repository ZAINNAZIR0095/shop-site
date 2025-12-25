<template>
    <div>
        <!-- Breadcrumb -->
        <CBreadcrumb class="my-3">
            <CBreadcrumbItem :to="{ name: 'dashboard' }">Dashboard</CBreadcrumbItem>
            <CBreadcrumbItem active>Stock Report</CBreadcrumbItem>
        </CBreadcrumb>

        <!-- Header Card -->
        <CCard class="mb-4">
            <CCardBody>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <!-- Title with Stats -->
                    <div>
                        <h1 class="text-h5 mb-2">Stock Management Report</h1>
                        <div class="d-flex flex-wrap gap-3">
                            <div>
                                <small class="text-muted">Total Products</small>
                                <h3 class="mb-0">{{ summary.total_products || 0 }}</h3>
                            </div>
                            <div>
                                <small class="text-muted">Total Stock Value</small>
                                <h3 class="mb-0">Rs. {{ formatCurrency(summary.total_stock_value || 0) }}</h3>
                            </div>
                            <div>
                                <small class="text-muted">Low Stock Items</small>
                                <h3 class="mb-0 text-warning">{{ summary.low_stock_items || 0 }}</h3>
                            </div>
                            <div>
                                <small class="text-muted">Out of Stock</small>
                                <h3 class="mb-0 text-danger">{{ summary.out_of_stock_items || 0 }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Export Buttons -->
                        <CButton color="success" @click="exportToExcel">
                            <CIcon name="cil-cloud-download" class="me-2" />
                            Export Excel
                        </CButton>
                        <CButton color="info" @click="printReport">
                            <CIcon name="cil-print" class="me-2" />
                            Print Report
                        </CButton>
                    </div>
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
                            @input="debounceSearch"
                        >
                        </CFormInput>
                    </CCol>

                    <!-- Date From -->
                    <CCol :md="2">
                        <CFormInput
                            v-model="filters.date_from"
                            type="date"
                            @change="fetchStockReport"
                            placeholder="Date From"
                        />
                    </CCol>

                    <!-- Date To -->
                    <CCol :md="2">
                        <CFormInput
                            v-model="filters.date_to"
                            type="date"
                            @change="fetchStockReport"
                            placeholder="Date To"
                        />
                    </CCol>

                    <!-- Min Limit -->
                    <CCol :md="2">
                        <CFormInput
                            v-model="filters.min_limit"
                            type="number"
                            min="0"
                            placeholder="Min Limit"
                            @input="debounceSearch"
                        />
                    </CCol>

                    <!-- Stock Status Filter -->
                    <CCol :md="2">
                        <CFormSelect v-model="filters.stock_status" @change="fetchStockReport">
                            <option value="">All Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="out">Out of Stock</option>
                            <option value="sufficient">Sufficient Stock</option>
                        </CFormSelect>
                    </CCol>
                </CRow>
            </CCardBody>
        </CCard>

        <!-- Stock Report Table -->
        <CCard>
            <CCardBody>
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
                        <CTable hover class="stock-table">
                            <CTableHead>
                                <CTableRow>
                                    <CTableHeaderCell>#</CTableHeaderCell>
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
                                    v-for="(product, index) in products"
                                    :key="product.id"
                                    :class="getStockRowClass(product)"
                                >
                                    <!-- Serial Number -->
                                    <CTableDataCell>
                                        {{ pagination.from + index }}
                                    </CTableDataCell>

                                    <!-- Product Information -->
                                    <CTableDataCell>
                                        <div>
                                            <strong>{{ product.name }}</strong>
                                            <div v-if="product.size" class="text-small text-muted">
                                                Size: {{ product.size }}
                                            </div>
                                        </div>
                                    </CTableDataCell>

                                    <!-- Type -->
                                    <CTableDataCell>
                                        {{ product.type || '-' }}
                                    </CTableDataCell>

                                    <!-- Unit -->
                                    <CTableDataCell>
                                        {{ product.unit || '-' }}
                                    </CTableDataCell>

                                    <!-- Current Stock -->
                                    <CTableDataCell class="text-center">
                                        <h5 class="mb-0" :class="getStockTextClass(product)">
                                            {{ product.current_stock || 0 }}
                                        </h5>
                                    </CTableDataCell>

                                    <!-- Min Limit -->
                                    <CTableDataCell class="text-center">
                                        {{ product.min_limit }}
                                    </CTableDataCell>

                                    <!-- Status -->
                                    <CTableDataCell class="text-center">
                                        <CBadge :color="getStockStatusColor(product)">
                                            {{ getStockStatus(product) }}
                                        </CBadge>
                                    </CTableDataCell>

                                    <!-- Purchase Price -->
                                    <CTableDataCell class="text-end">
                                        <strong>Rs. {{ formatCurrency(product.purchase_price || 0) }}</strong>
                                    </CTableDataCell>

                                    <!-- Sale Price -->
                                    <CTableDataCell class="text-end">
                                        <strong class="text-success">Rs. {{ formatCurrency(product.sale_price || 0) }}</strong>
                                    </CTableDataCell>

                                    <!-- Stock Value -->
                                    <CTableDataCell class="text-end">
                                        <strong class="text-primary">
                                            Rs. {{ formatCurrency(product.stock_value || 0) }}
                                        </strong>
                                    </CTableDataCell>
                                </CTableRow>
                            </CTableBody>
                        </CTable>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} products
                        </div>
                        <CPagination v-if="pagination.last_page > 1">
                            <CPaginationItem
                                :disabled="pagination.current_page === 1"
                                @click="changePage(pagination.current_page - 1)"
                            >
                                Previous
                            </CPaginationItem>

                            <CPaginationItem
                                v-for="page in visiblePages"
                                :key="page"
                                :active="page === pagination.current_page"
                                @click="changePage(page)"
                            >
                                {{ page }}
                            </CPaginationItem>

                            <CPaginationItem
                                :disabled="pagination.current_page === pagination.last_page"
                                @click="changePage(pagination.current_page + 1)"
                            >
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
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'

// CoreUI Components
import {
    CCard, CCardBody,
    CTable, CTableHead, CTableBody, CTableRow, CTableHeaderCell, CTableDataCell,
    CSpinner, CBadge, CPagination, CPaginationItem,
    CBreadcrumb, CBreadcrumbItem,
    CButton,
    CRow, CCol,
    CFormSelect, CFormInput,
    CCollapse,
} from '@coreui/vue'

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
const showAdvancedFilters = ref(false)

// Filters
const filters = reactive({
    search: '',
    date_from: '',
    date_to: '',
    min_limit: '',
    stock_status: '',
    min_stock: '',
    max_stock: '',
    sort_by: '',
    page: 1,
    per_page: 15
})

// Methods
const fetchStockReport = async () => {
    loading.value = true
    try {
        const params = { ...filters }

        // Remove empty filters
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                delete params[key]
            }
        })

        const response = await axios.get('/stocks-reports', { params }) // Changed endpoint

        if (response.data.success) {
            products.value = response.data.data.products || []
            summary.value = response.data.data.summary || {}

            // Update pagination - adjust based on your actual response structure
            if (response.data.data.pagination) {
                Object.assign(pagination, response.data.data.pagination)
            } else {
                // Fallback if pagination structure is different
                pagination.current_page = response.data.data.current_page || 1
                pagination.last_page = response.data.data.last_page || 1
                pagination.total = response.data.data.total || 0
                pagination.per_page = response.data.data.per_page || 15
                pagination.from = response.data.data.from || 0
                pagination.to = response.data.data.to || 0
            }
        }
    } catch (error) {
        console.error('Error fetching stock report:', error)
    } finally {
        loading.value = false
    }
}

const debounceSearch = debounce(() => {
    filters.page = 1
    fetchStockReport()
}, 500)

const resetFilters = () => {
    filters.search = ''
    filters.date_from = ''
    filters.date_to = ''
    filters.min_limit = ''
    filters.stock_status = ''
    filters.min_stock = ''
    filters.max_stock = ''
    filters.sort_by = ''
    filters.page = 1
    fetchStockReport()
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        filters.page = page
        fetchStockReport()
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const exportToExcel = async () => {
    try {
        const params = { ...filters }
        const response = await axios.get('/stocks/export', {
            params,
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `stock-report-${new Date().toISOString().split('T')[0]}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        console.error('Error exporting to Excel:', error)
        // Try JSON export as fallback
        try {
            const params = { ...filters }
            const response = await axios.get('/stocks/export', { params })

            if (response.data.success) {
                // Convert JSON to CSV for download
                const csvData = convertToCSV(response.data.data)
                const blob = new Blob([csvData], { type: 'text/csv' })
                const url = window.URL.createObjectURL(blob)
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', `stock-report-${new Date().toISOString().split('T')[0]}.csv`)
                document.body.appendChild(link)
                link.click()
                link.remove()
            }
        } catch (jsonError) {
            console.error('JSON export also failed:', jsonError)
        }
    }
}

const convertToCSV = (data) => {
    if (!data || data.length === 0) return ''

    const headers = Object.keys(data[0]).join(',')
    const rows = data.map(row =>
        Object.values(row).map(value =>
            typeof value === 'string' ? `"${value.replace(/"/g, '""')}"` : value
        ).join(',')
    )

    return [headers, ...rows].join('\n')
}

const printReport = () => {
    // Create printable content
    const printContent = `
        <html>
            <head>
                <title>Stock Report - ${new Date().toLocaleDateString()}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; font-weight: bold; }
                    .summary { margin-bottom: 20px; }
                    .summary-item { display: inline-block; margin-right: 20px; }
                    .low-stock { background-color: #fff3cd; }
                    .out-of-stock { background-color: #f8d7da; }
                </style>
            </head>
            <body>
                <h1>Stock Management Report</h1>
                <div class="summary">
                    <div class="summary-item"><strong>Total Products:</strong> ${summary.value.total_products || 0}</div>
                    <div class="summary-item"><strong>Total Stock Value:</strong> Rs. ${formatCurrency(summary.value.total_stock_value || 0)}</div>
                    <div class="summary-item"><strong>Low Stock Items:</strong> ${summary.value.low_stock_items || 0}</div>
                    <div class="summary-item"><strong>Out of Stock:</strong> ${summary.value.out_of_stock_items || 0}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Current Stock</th>
                            <th>Min Limit</th>
                            <th>Status</th>
                            <th>Purchase Price</th>
                            <th>Sale Price</th>
                            <th>Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${products.value.map((product, index) => `
                            <tr class="${getStockRowClass(product)}">
                                <td>${index + 1}</td>
                                <td>${product.name}</td>
                                <td>${product.type || '-'}</td>
                                <td>${product.unit || '-'}</td>
                                <td>${product.current_stock || 0}</td>
                                <td>${product.min_limit || 5}</td>
                                <td>${getStockStatus(product)}</td>
                                <td>Rs. ${formatCurrency(product.purchase_price || 0)}</td>
                                <td>Rs. ${formatCurrency(product.sale_price || 0)}</td>
                                <td>Rs. ${formatCurrency(product.stock_value || 0)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div style="margin-top: 20px; font-size: 12px; color: #666;">
                    Generated on: ${new Date().toLocaleString()}
                </div>
            </body>
        </html>
    `

    const printWindow = window.open('', '_blank')
    printWindow.document.write(printContent)
    printWindow.document.close()
    printWindow.focus()
    printWindow.print()
    printWindow.close()
}

// Helper Methods
const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const getStockStatus = (product) => {
    const stock = product.current_stock || 0
    const minStock = product.min_limit || 5

    if (stock == 0) return 'Out of Stock'
    if (stock <= minStock) return 'Low Stock'
    return 'In Stock'
}

const getStockStatusColor = (product) => {
    const stock = product.current_stock || 0
    const minStock = product.min_limit || 5

    if (stock === 0) return 'danger'
    if (stock <= minStock) return 'warning'
    return 'success'
}

const getStockTextClass = (product) => {
    const stock = product.current_stock || 0
    const minStock = product.min_limit || 5

    if (stock === 0) return 'text-danger'
    if (stock <= minStock) return 'text-warning'
    return 'text-success'
}

const getStockRowClass = (product) => {
    const stock = product.current_stock || 0
    const minStock = product.min_limit || 5

    if (stock === 0) return 'out-of-stock'
    if (stock <= minStock) return 'low-stock'
    return ''
}

const visiblePages = computed(() => {
    const current = pagination.current_page
    const last = pagination.last_page
    const delta = 2
    const range = []

    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i)
    }

    if (current - delta > 2) {
        range.unshift('...')
    }

    if (current + delta < last - 1) {
        range.push('...')
    }

    range.unshift(1)
    if (last > 1) range.push(last)

    return range.filter(page => page !== '...' || true)
})

// Enhanced product transformation to handle different data structures
const transformProductForDisplay = (product) => {
    // Your product data might come in different structures
    // This ensures we always have the needed properties
    return {
        id: product.id || product.product_id,
        name: product.name || 'Unknown Product',
        type: product.type || product.product_type || '-',
        unit: product.unit || '-',
        size: product.size || '-',
        min_limit: product.min_limit || product.min_limit || 5,
        sale_price: product.sale_price || product.selling_price || 0,
        purchase_price: product.purchase_price || product.cost_price || 0,
        current_stock: product.current_stock || product.stock_quantity || 0,
        stock_value: product.stock_value || (product.current_stock || 0) * (product.purchase_price || 0),
        code: product.code || product.product_code || 'N/A',
        description: product.description || '',
        image_url: product.image_url || product.image || '/images/default-product.png',
        category: product.category || null,
        supplier: product.supplier || null,
        created_at: product.created_at,
        updated_at: product.updated_at
    }
}

// Lifecycle
onMounted(() => {
    fetchStockReport()
})
</script>
