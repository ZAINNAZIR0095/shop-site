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
                            <p class="mb-0">{{ stock.id || 'N/A' }}</p>
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
                        <strong>Reference:</strong> {{ stock.id || 'N/A' }}<br>
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
import Swal from 'sweetalert2'

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
    Swal.fire({
        title: 'Deleted!',
        text: 'Stock entry deleted successfully!',
        icon: 'success'
    }).then(() => {
        // Redirect to stocks list
        router.push({ name: 'stocks' })
    })
} else {
    Swal.fire({
        title: 'Delete Failed',
        text: response.data.message || 'Failed to delete stock entry',
        icon: 'error'
    })
}

} catch (err) {
    console.error('Error deleting stock:', err)

    Swal.fire({
        title: 'Error',
        text: 'An error occurred while deleting the stock entry.',
        icon: 'error'
    })
}finally {
        deleting.value = false
        showDeleteModal.value = false
    }
}

const printDetails = () => {
    const printWindow = window.open('', '_blank')
    const now = new Date()

    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                /* RECEIPT-SPECIFIC STYLES FOR 80MM PAPER */
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 11px;
                    width: 80mm;
                    margin: 0 auto;
                    padding: 5px;
                    line-height: 1.2;
                }

                /* Center alignment for receipt */
                .center {
                    text-align: center;
                }

                /* Store header */
                .store-header {
                    font-weight: bold;
                    margin-bottom: 5px;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 5px;
                }

                .store-name {
                    font-size: 14px;
                    text-transform: uppercase;
                }

                .store-phone {
                    font-size: 10px;
                }

                /* Receipt details */
                .receipt-info {
                    margin: 8px 0;
                    font-size: 10px;
                }

                .receipt-info div {
                    margin: 2px 0;
                }

                /* Items table - compact */
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 8px 0;
                    font-size: 10px;
                }

                .items-table th {
                    text-align: left;
                    border-bottom: 1px solid #000;
                    padding: 3px 0;
                }

                .items-table td {
                    padding: 2px 0;
                    vertical-align: top;
                }

                .qty { width: 15%; }
                .desc { width: 45%; }
                .price { width: 20%; text-align: right; }
                .total { width: 20%; text-align: right; }

                /* Total section */
                .total-section {
                    border-top: 2px solid #000;
                    margin-top: 10px;
                    padding-top: 5px;
                    font-weight: bold;
                }

                /* Footer */
                .footer {
                    font-size: 9px;
                    margin-top: 15px;
                    padding-top: 5px;
                    border-top: 1px dashed #000;
                }

                /* Print optimization */
                @media print {
                    body {
                        width: 80mm !important;
                        margin: 0 !important;
                        padding: 2mm !important;
                    }

                    @page {
                        margin: 0;
                        size: auto;
                    }
                }
            </style>
        </head>
        <body>
            <div class="center">
                <!-- Store Header -->
                <div class="store-header">
                    <div class="store-name">SHAHZAIB ELECTRIC STORE</div>
                    <div class="store-phone">📞 0308-8840832</div>
                    <div style="font-size: 10px;">Lahore, Pakistan</div>
                </div>

                <!-- Receipt Type -->
                <div style="margin: 5px 0; font-weight: bold;">
                    ${formatStockType(stock.value.stock_type).toUpperCase()} RECEIPT
                </div>

                <!-- Receipt Details -->
                <div class="receipt-info">
                    <div>Ref: #${stock.value.id}</div>
                    <div>Date: ${formatDate(stock.value.date)}</div>
                    <div>Time: ${new Date(stock.value.date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>

                    ${stock.value.party_name ? `
                    <div style="margin-top: 5px;">
                        <div><strong>${stock.value.stock_type === 'sale' ? 'Customer' : 'Supplier'}:</strong></div>
                        <div>${stock.value.party_name}</div>
                        ${stock.value.party_phone ? `<div>Phone: ${stock.value.party_phone}</div>` : ''}
                    </div>
                    ` : ''}
                </div>

                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">

                <!-- Items Table -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="desc">Product</th>
                            <th class="qty">Qty</th>
                            <th class="price">Price</th>
                            <th class="total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stock.value.items ? stock.value.items.map((item, index) => `
                        <tr>
                            <td class="desc">
                                ${item.product?.name || 'Product'}
                            </td>
                            <td class="qty">${item.quantity}</td>
                            <td class="price">${formatCurrency(item.unit_price)}</td>
                            <td class="total">${formatCurrency(item.total_price)}</td>
                        </tr>
                        `).join('') : ''}
                    </tbody>
                </table>

                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">

                <!-- Total -->
                <div class="total-section">
                    <div style="display: flex; justify-content: space-between;">
                        <span>TOTAL:</span>
                        <span>PKR ${formatCurrency(stock.value.net_price)}</span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <div>Printed: ${now.toLocaleDateString()} ${now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                    <div style="margin-top: 5px;">Thank you for your business!</div>
                    <div style="font-size: 8px; margin-top: 3px;">
                        *Goods once sold are not returnable*
                    </div>
                </div>
            </div>
        </body>
        </html>
    `

    printWindow.document.write(printContent)
    printWindow.document.close()

    // Small delay for rendering then print
    setTimeout(() => {
        printWindow.focus()
        printWindow.print()
        printWindow.close()
    }, 200)
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
