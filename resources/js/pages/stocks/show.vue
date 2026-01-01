<template>
    <div>
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <CSpinner color="primary" />
            <p class="mt-2">Loading stock details...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
            <CIcon name="cil-warning" class="me-2" />
            {{ error }}
            <CButton color="light" size="sm" class="ms-3" @click="goBack">Go Back</CButton>
        </div>

        <!-- Content -->
        <div v-else>
            <!-- Header Card -->
            <CCard class="mb-4">
                <CCardHeader class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Stock Entry Details</strong>
                        <span class="ms-3 badge" :class="getStockTypeBadgeClass(stock.stock_type)">
                            {{ formatStockType(stock.stock_type) }}
                        </span>
                    </div>
                    <div>
                        <CButton color="warning" size="sm" :to="{ name: 'stocks.edit', params: { id: stock.id } }" class="me-2">
                            <CIcon name="cil-pencil" /> Edit
                        </CButton>
                        <CButton color="secondary" size="sm" @click="goBack">
                            <CIcon name="cil-arrow-left" /> Back
                        </CButton>
                    </div>
                </CCardHeader>
                <CCardBody>
                    <CRow>
                        <CCol :md="3" class="mb-3">
                            <strong>Reference No:</strong>
                            <p class="mb-0">{{ stock.reference_no || 'N/A' }}</p>
                        </CCol>
                        <CCol :md="3" class="mb-3">
                            <strong>Date:</strong>
                            <p class="mb-0">{{ formatDate(stock.date) }}</p>
                        </CCol>
                        <CCol :md="3" class="mb-3">
                            <strong>Created By:</strong>
                            <p class="mb-0">{{ stock.user?.name || 'System' }}</p>
                        </CCol>
                        <CCol :md="3" class="mb-3">
                            <strong>Total Amount:</strong>
                            <p class="mb-0 text-primary fw-bold">PKR {{ formatCurrency(stock.net_price) }}</p>
                        </CCol>
                    </CRow>

                    <!-- Party Information -->
                    <div v-if="stock.party_name || stock.party_phone || stock.party_address" class="mt-3 pt-3 border-top">
                        <h6>{{ stock.stock_type === 'sale' ? 'Customer' : 'Supplier' }} Information</h6>
                        <CRow>
                            <CCol :md="4" class="mb-2">
                                <strong>Name:</strong>
                                <p class="mb-0">{{ stock.party_name || 'N/A' }}</p>
                            </CCol>
                            <CCol :md="4" class="mb-2">
                                <strong>Phone:</strong>
                                <p class="mb-0">{{ stock.party_phone || 'N/A' }}</p>
                            </CCol>
                            <CCol :md="4" class="mb-2">
                                <strong>Address:</strong>
                                <p class="mb-0">{{ stock.party_address || 'N/A' }}</p>
                            </CCol>
                        </CRow>
                    </div>

                    <!-- Description -->
                    <div v-if="stock.description" class="mt-3 pt-3 border-top">
                        <strong>Description:</strong>
                        <p class="mb-0">{{ stock.description }}</p>
                    </div>
                </CCardBody>
            </CCard>

            <!-- Products Card -->
            <CCard>
                <CCardHeader>
                    <strong>Products ({{ stock.items?.length || 0 }})</strong>
                </CCardHeader>
                <CCardBody>
                    <!-- Empty State -->
                    <div v-if="!stock.items || stock.items.length === 0" class="text-center py-4">
                        <CIcon name="cil-cart" size="xl" class="text-muted mb-2" />
                        <p class="text-muted mb-0">No products found in this stock entry.</p>
                    </div>

                    <!-- Products Table -->
                    <div v-else class="table-responsive">
                        <CTable hover striped>
                            <CTableHead>
                                <CTableRow>
                                    <CTableHeaderCell>#</CTableHeaderCell>
                                    <CTableHeaderCell>Product</CTableHeaderCell>
                                    <CTableHeaderCell>Quantity</CTableHeaderCell>
                                    <CTableHeaderCell>Unit Price (PKR)</CTableHeaderCell>
                                    <CTableHeaderCell>Total (PKR)</CTableHeaderCell>
                                </CTableRow>
                            </CTableHead>
                            <CTableBody>
                                <CTableRow v-for="(item, index) in stock.items" :key="item.id">
                                    <CTableDataCell>{{ index + 1 }}</CTableDataCell>
                                    <CTableDataCell>
                                        <div>
                                            <strong>{{ item.product?.name || 'Product #' + item.product_id }}</strong>
                                            <div v-if="item.product?.unit" class="small text-muted">
                                                Unit: {{ item.product.unit }}
                                            </div>
                                        </div>
                                    </CTableDataCell>
                                    <CTableDataCell>
                                        <span class="fw-bold">{{ item.quantity }}</span>
                                        <span v-if="item.product?.unit" class="text-muted ms-1">
                                            {{ item.product.unit }}
                                        </span>
                                    </CTableDataCell>
                                    <CTableDataCell>
                                        PKR {{ formatCurrency(item.unit_price) }}
                                    </CTableDataCell>
                                    <CTableDataCell>
                                        <strong class="text-primary">
                                            PKR {{ formatCurrency(item.total_price || (item.quantity * item.unit_price)) }}
                                        </strong>
                                    </CTableDataCell>
                                </CTableRow>
                            </CTableBody>
                            <CTableFoot>
                                <CTableRow>
                                    <CTableDataCell colspan="3"></CTableDataCell>
                                    <CTableDataCell class="text-end">
                                        <strong>Subtotal:</strong>
                                    </CTableDataCell>
                                    <CTableDataCell>
                                        <strong>PKR {{ formatCurrency(subtotal) }}</strong>
                                    </CTableDataCell>
                                </CTableRow>
                                <CTableRow>
                                    <CTableDataCell colspan="3"></CTableDataCell>
                                    <CTableDataCell class="text-end">
                                        <strong>Total Amount:</strong>
                                    </CTableDataCell>
                                    <CTableDataCell>
                                        <h5 class="text-primary mb-0">PKR {{ formatCurrency(stock.net_price) }}</h5>
                                    </CTableDataCell>
                                </CTableRow>
                            </CTableFoot>
                        </CTable>
                    </div>
                </CCardBody>
            </CCard>

            <!-- Actions Card -->
            <CCard class="mt-4">
                <CCardBody>
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted">
                                Created: {{ formatDateTime(stock.created_at) }}
                                <span v-if="stock.updated_at !== stock.created_at">
                                    | Last Updated: {{ formatDateTime(stock.updated_at) }}
                                </span>
                            </small>
                        </div>
                        <div>
                            <CButton color="outline-danger" size="sm" @click="confirmDelete" class="me-2">
                                <CIcon name="cil-trash" /> Delete
                            </CButton>
                            <CButton color="primary" @click="printDetails">
                                <CIcon name="cil-print" /> Print
                            </CButton>
                        </div>
                    </div>
                </CCardBody>
            </CCard>
        </div>

        <!-- Delete Confirmation Modal -->
        <CModal :visible="showDeleteModal" @close="showDeleteModal = false">
            <CModalHeader>
                <CModalTitle>Confirm Delete</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="text-center py-3">
                    <CIcon name="cil-warning" size="3xl" class="text-warning mb-3" />
                    <h5>Are you sure you want to delete this stock entry?</h5>
                    <p class="mb-0">
                        <strong>Reference:</strong> {{ stock.reference_no || 'N/A' }}<br>
                        <strong>Type:</strong> {{ formatStockType(stock.stock_type) }}<br>
                        <strong>Date:</strong> {{ formatDate(stock.date) }}<br>
                        <strong>Amount:</strong> PKR {{ formatCurrency(stock.net_price) }}
                    </p>
                    <p class="text-danger mt-3">
                        <small>
                            <CIcon name="cil-warning" />
                            This action cannot be undone. All associated stock adjustments will be reversed.
                        </small>
                    </p>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="showDeleteModal = false">Cancel</CButton>
                <CButton color="danger" @click="deleteStock" :disabled="deleting">
                    <CSpinner v-if="deleting" component="span" size="sm" class="me-2" />
                    {{ deleting ? 'Deleting...' : 'Yes, Delete' }}
                </CButton>
            </CModalFooter>
        </CModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

// CoreUI Components
import {
    CCard, CCardHeader, CCardBody,
    CButton, CSpinner,
    CRow, CCol,
    CTable, CTableHead, CTableBody, CTableFoot, CTableRow, CTableHeaderCell, CTableDataCell,
    CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
} from '@coreui/vue'

const router = useRouter()
const route = useRoute()

// State
const stock = ref({})
const loading = ref(true)
const error = ref('')
const showDeleteModal = ref(false)
const deleting = ref(false)

// Computed
const subtotal = computed(() => {
    if (!stock.value.items) return 0
    return stock.value.items.reduce((sum, item) => {
        return sum + (item.total_price || (item.quantity * item.unit_price))
    }, 0)
})

// Methods
const fetchStock = async () => {
    try {
        loading.value = true
        error.value = ''

        const response = await axios.get(`/stocks/${route.params.id}`)
console.log(response)
        if (response.data.success) {
            stock.value = response.data.data
        } else {
            error.value = response.data.message || 'Failed to load stock details'
        }
    } catch (err) {
        console.error('Error fetching stock:', err)
        if (err.response?.status === 404) {
            error.value = 'Stock entry not found.'
        } else {
            error.value = 'An error occurred while loading stock details.'
        }
    } finally {
        loading.value = false
    }
}

const formatStockType = (type) => {
    const types = {
        purchase: 'Purchase',
        sale: 'Sale',
        issue: 'Issue',
        return: 'Return'
    }
    return types[type] || type
}

const getStockTypeBadgeClass = (type) => {
    const classes = {
        purchase: 'bg-success',
        sale: 'bg-info',
        issue: 'bg-warning',
        return: 'bg-secondary'
    }
    return classes[type] || 'bg-primary'
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatCurrency = (amount) => {
    const num = parseFloat(amount || 0)
    return num.toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const goBack = () => {
    router.back()
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteStock = async () => {
    try {
        deleting.value = true

        const response = await axios.delete(`/stocks/${route.params.id}`)

        if (response.data.success) {
            // Show success message
            alert('Stock entry deleted successfully!')
            // Redirect to stocks list
            router.push({ name: 'stocks' })
        } else {
            alert(response.data.message || 'Failed to delete stock entry')
        }
    } catch (err) {
        console.error('Error deleting stock:', err)
        alert('An error occurred while deleting the stock entry.')
    } finally {
        deleting.value = false
        showDeleteModal.value = false
    }
}

const printDetails = () => {
    window.print()
}

// Lifecycle
onMounted(() => {
    fetchStock()
})
</script>

<style scoped>
.badge {
    padding: 0.35rem 0.65rem;
    font-size: 0.85rem;
    font-weight: 600;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

/* Print styles */
@media print {
    .breadcrumb,
    .btn,
    .modal {
        display: none !important;
    }

    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6;
    }

    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}
</style>
