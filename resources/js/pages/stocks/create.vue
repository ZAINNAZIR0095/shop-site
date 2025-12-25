    <!-- resources/js/views/stocks/CreateStock.vue -->
<template>
    <div>
        <!-- Breadcrumb -->
        <CBreadcrumb class="my-3">
            <CBreadcrumbItem :to="{ name: 'home' }">Dashboard</CBreadcrumbItem>
            <CBreadcrumbItem :to="{ name: 'stocks' }">Stocks</CBreadcrumbItem>
            <CBreadcrumbItem active>Create Stock</CBreadcrumbItem>
        </CBreadcrumb>

        <!-- Form Card -->
        <CCard>
            <CCardHeader>
                <strong>Create New Stock Entry</strong>
            </CCardHeader>
            <CCardBody>
                <CForm @submit.prevent="submitForm">
                    <!-- Stock Type Selection -->
                    <div class="mb-4">
                        <h5 class="mb-3">Transaction Type</h5>
                        <div class="d-flex flex-wrap gap-3">
                            <CFormCheck type="radio" name="stock_type" value="purchase" id="type_purchase"
                                v-model="form.stock_type" label="Purchase" inline @change="handleStockTypeChange" />
                            <CFormCheck type="radio" name="stock_type" value="sale" id="type_sale"
                                v-model="form.stock_type" label="Sale" inline @change="handleStockTypeChange" />
                            <CFormCheck type="radio" name="stock_type" value="issue" id="type_issue"
                                v-model="form.stock_type" label="Issue" inline @change="handleStockTypeChange" />
                            <CFormCheck type="radio" name="stock_type" value="return" id="type_return"
                                v-model="form.stock_type" label="Return" inline @change="handleStockTypeChange" />
                        </div>
                        <CFormFeedback v-if="errors.stock_type" invalid>
                            {{ errors.stock_type[0] }}
                        </CFormFeedback>
                    </div>

                    <!-- Basic Information -->
                    <CRow class="mb-4">
                        <CCol :md="6">
                            <CFormLabel for="date">Date <span class="text-danger">*</span></CFormLabel>
                            <CFormInput id="date" v-model="form.date" type="date" :invalid="errors.date"
                                @input="clearError('date')" required />
                            <CFormFeedback v-if="errors.date" invalid>
                                {{ errors.date[0] }}
                            </CFormFeedback>
                        </CCol>

                        <CCol :md="6">
                            <CFormLabel for="party_name">
                                {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }} Name
                            </CFormLabel>
                            <CFormInput id="party_name" v-model="form.party_name" type="text"
                                :placeholder="form.stock_type === 'sale' ? 'Enter customer name' : 'Enter supplier name'"
                                :invalid="errors.party_name" @input="clearError('party_name')" />
                            <CFormFeedback v-if="errors.party_name" invalid>
                                {{ errors.party_name[0] }}
                            </CFormFeedback>
                        </CCol>
                    </CRow>

                    <!-- Contact Information -->
                    <CRow class="mb-4">
                        <CCol :md="6">
                            <CFormLabel for="party_phone">Phone</CFormLabel>
                            <CFormInput id="party_phone" v-model="form.party_phone" type="text"
                                placeholder="Phone number" :invalid="errors.party_phone"
                                @input="clearError('party_phone')" />
                        </CCol>

                        <CCol :md="6">
                            <CFormLabel for="party_address">Address</CFormLabel>
                            <CFormInput id="party_address" v-model="form.party_address" type="text"
                                placeholder="Address" :invalid="errors.party_address"
                                @input="clearError('party_address')" />
                        </CCol>
                    </CRow>

                    <!-- Description -->
                    <div class="mb-4">
                        <CFormLabel for="description">Description</CFormLabel>
                        <CTextarea id="description" v-model="form.description"
                            placeholder="Additional notes or description..." rows="2" :invalid="errors.description"
                            @input="clearError('description')" />
                    </div>

                    <!-- Products Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Products</h5>
                            <CButton color="primary" size="sm" @click="addProduct">
                                <CIcon name="cil-plus" class="me-1" />
                                Add Product
                            </CButton>
                        </div>

                        <!-- Empty State -->
                        <div v-if="form.items.length === 0" class="text-center py-4 border rounded">
                            <CIcon name="cil-cart" size="2xl" class="text-muted mb-2" />
                            <p class="mb-0">No products added yet.</p>
                            <p class="text-muted">Click "Add Product" to start adding items.</p>
                        </div>

                        <!-- Products Table -->
                        <div v-else class="table-responsive">
                            <CTable hover striped>
                                <CTableHead>
                                    <CTableRow>
                                        <CTableHeaderCell>Product</CTableHeaderCell>
                                        <CTableHeaderCell>Current Stock</CTableHeaderCell>
                                        <CTableHeaderCell>Quantity</CTableHeaderCell>
                                        <CTableHeaderCell>Unit Price (PKR)</CTableHeaderCell>
                                        <CTableHeaderCell>Total (PKR)</CTableHeaderCell>
                                        <CTableHeaderCell class="text-end">Actions</CTableHeaderCell>
                                    </CTableRow>
                                </CTableHead>
                                <CTableBody>
                                    <CTableRow v-for="(item, index) in form.items" :key="index">
                                        <!-- Product Selection -->
                                        <CTableDataCell>
                                            <CFormSelect v-model="item.product_id"
                                                :invalid="errors[`items.${index}.product_id`]"
                                                @change="handleProductChange(index)" required>
                                                <option value="">Select Product</option>
                                                <option v-for="product in availableProducts" :key="product.id"
                                                    :value="product.id"
                                                    :disabled="isProductSelected(product.id, index)">
                                                    {{ product.name }}
                                                    <span v-if="product.unit">({{ product.unit }})</span>
                                                    <span class="text-muted small">
                                                        [Sale: {{ product.sale_price || 'N/A' }} |
                                                        Purchase: {{ product.purchase_price || 'N/A' }}]
                                                    </span>
                                                </option>
                                            </CFormSelect>
                                            <CFormFeedback v-if="errors[`items.${index}.product_id`]" invalid>
                                                {{ errors[`items.${index}.product_id`][0] }}
                                            </CFormFeedback>
                                        </CTableDataCell>

                                        <!-- Current Stock -->
                                        <CTableDataCell>
                                            <span v-if="item.product_id" class="badge"
                                                :class="getStockBadgeClass(item)">
                                                {{ getProductStock(item.product_id) }}
                                            </span>
                                            <span v-else class="text-muted">-</span>
                                        </CTableDataCell>

                                        <!-- Quantity -->
                                        <CTableDataCell>
                                            <CInputGroup>
                                                <CFormInput :value="Number(item.quantity)"
                                                    @input="item.quantity = parseFloat($event.target.value) || 0; updateItemTotal(index)"
                                                    type="number" min="1" :invalid="errors[`items.${index}.quantity`]"
                                                    required />
                                                <CInputGroupText v-if="getProductUnit(item.product_id)">
                                                    {{ getProductUnit(item.product_id) }}
                                                </CInputGroupText>
                                            </CInputGroup>
                                            <CFormFeedback v-if="errors[`items.${index}.quantity`]" invalid>
                                                {{ errors[`items.${index}.quantity`][0] }}
                                            </CFormFeedback>
                                        </CTableDataCell>

                                        <!-- Unit Price -->
          <CTableDataCell>
    <CInputGroup>
        <CInputGroupText>PKR</CInputGroupText>
        <CFormInput :value="getUnitPrice(item.product_id)"
            type="number" step="0.01" min="0" readonly />
    </CInputGroup>
</CTableDataCell>
                                        <!-- Total -->
                                        <CTableDataCell>
                                            <strong class="text-primary">
                                                PKR {{ formatCurrency(item.total || 0) }}
                                            </strong>
                                        </CTableDataCell>

                                        <!-- Actions -->
                                        <CTableDataCell class="text-end">
                                            <CButton color="danger" size="sm" variant="outline"
                                                @click="removeProduct(index)" title="Remove">
                                                <CIcon name="cil-trash" />
                                            </CButton>
                                        </CTableDataCell>
                                    </CTableRow>
                                </CTableBody>
                                <CTableFoot v-if="form.items.length > 0">
                                    <CTableRow>
                                        <CTableDataCell colspan="4" class="text-end">
                                            <strong>Total Amount:</strong>
                                        </CTableDataCell>
                                        <CTableDataCell colspan="2">
                                            <h5 class="text-primary mb-0">PKR {{ formatCurrency(totalAmount) }}</h5>
                                        </CTableDataCell>
                                    </CTableRow>
                                </CTableFoot>
                            </CTable>
                        </div>

                        <div v-if="errors.items" class="alert alert-danger mt-2">
                            {{ errors.items[0] }}
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between mt-4">
                        <CButton color="secondary" @click="goBack">
                            <CIcon name="cil-arrow-left" /> Back
                        </CButton>
                        <div>
                            <CButton color="light" class="me-2" @click="resetForm" type="button">
                                <CIcon name="cil-reload" /> Reset
                            </CButton>
                            <CButton type="submit" color="primary" :disabled="submitting">
                                <CSpinner v-if="submitting" component="span" size="sm" class="me-2" />
                                <CIcon v-else name="cil-save" class="me-2" />
                                {{ submitting ? 'Saving...' : 'Save Stock Entry' }}
                            </CButton>
                        </div>
                    </div>
                </CForm>
            </CCardBody>
        </CCard>

        <!-- Success Modal -->
        <CModal :visible="showSuccessModal" @close="handleModalClose">
            <CModalHeader>
                <CModalTitle>Success!</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="text-center py-4">
                    <CIcon name="cil-check-circle" size="3xl" class="text-success mb-3" />
                    <h5>Stock Entry Created Successfully!</h5>
                    <p class="mb-0">{{ successMessage }}</p>
                    <div class="mt-3">
                        <p><strong>Reference:</strong> {{ createdStock?.reference_no || 'N/A' }}</p>
                        <p><strong>Type:</strong> {{ formatStockType(createdStock?.stock_type) }}</p>
                        <p><strong>Total Amount:</strong> PKR {{ formatCurrency(createdStock?.net_price) }}</p>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="primary" @click="viewStock">View Details</CButton>
                <CButton color="secondary" @click="createAnother">Create Another</CButton>
            </CModalFooter>
        </CModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

// CoreUI Components
import {
    CCard, CCardHeader, CCardBody,
    CForm, CFormLabel, CFormInput, CFormSelect, CFormCheck, CFormFeedback,
    CButton, CSpinner, CButtonGroup,
    CRow, CCol,
    CInputGroup, CInputGroupText,
    CTable, CTableHead, CTableBody, CTableFoot, CTableRow, CTableHeaderCell, CTableDataCell,
    CBreadcrumb, CBreadcrumbItem,
    CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
} from '@coreui/vue'


const router = useRouter()

// State
const availableProducts = ref([])
const submitting = ref(false)
const showSuccessModal = ref(false)
const successMessage = ref('')
const createdStock = ref(null)
const errors = ref({})

// Form data
const form = reactive({
    stock_type: 'purchase',
    date: new Date().toISOString().split('T')[0], // Today's date
    description: '',
    party_name: '',
    party_phone: '',
    party_address: '',
    items: []
})

// Computed properties
const totalAmount = computed(() => {
    return form.items.reduce((sum, item, index) => {
        const quantity = parseFloat(item.quantity) || 0
        const unitPrice = getUnitPrice(item.product_id)
        return sum + (quantity * unitPrice)
    }, 0)
})


// Methods
const fetchProducts = async () => {
    try {
        const response = await axios.get('/products')
        console.log('product data', response.data.data)
        availableProducts.value = response.data.data
    } catch (error) {
        console.error('Error fetching products:', error)
    }
}

const handleStockTypeChange = () => {
    form.party_name = ''
    form.party_phone = ''
    form.party_address = ''

    // Recalculate totals for all items
    form.items.forEach((item, index) => {
        if (item.product_id) {
            updateItemTotal(index)
        }
    })
}

const addProduct = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
        total: 0
    })
}

const removeProduct = (index) => {
    form.items.splice(index, 1)
}
const getUnitPrice = (productId) => {
    if (!productId) return 0
    const product = availableProducts.value.find(p => p.id == productId)
    if (!product) return 0
    console.log(product.sale_price)
    return form.stock_type === 'sale'
        ? parseFloat(product.sale_price) || 0
        : parseFloat(product.purchase_price) || 0
}

const handleProductChange = (index) => {
    // Just update the total when product changes
    updateItemTotal(index)
}


    const product = availableProducts.value.find(p => p.id == productId)
    if (product) {
        // Set unit price based on stock type
        form.items[index].sale_price = form.stock_type === 'sale'
            ? product.sale_price
            : product.purchase_price

        // Update total
        updateItemTotal(index)
    }


const updateItemTotal = (index) => {
    const item = form.items[index]
    const quantity = parseFloat(item.quantity) || 0
    const unitPrice = getUnitPrice(item.product_id)  // Use the function
    item.total = quantity * unitPrice
}

const isProductSelected = (productId, currentIndex) => {
    return form.items.some((item, index) =>
        index !== currentIndex && item.product_id == productId
    )
}

const getProductStock = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return 0
}

const getProductUnit = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return product ? product.unit : ''
}

const getStockBadgeClass = (item) => {
    const currentStock = getProductStock(item.product_id)
    const quantity = parseInt(item.quantity) || 0

    if (!item.product_id) return 'bg-secondary'

    if (form.stock_type === 'sale' || form.stock_type === 'issue') {
        if (quantity > currentStock) return 'bg-danger'
    }

    if (currentStock <= 0) return 'bg-danger'
    if (currentStock <= 10) return 'bg-warning'
    return 'bg-success'
}

const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field]
    }

    // Clear nested errors
    if (field.includes('items')) {
        const baseField = field.split('.')[0]
        if (errors.value[baseField]) {
            delete errors.value[baseField]
        }
    }
}

const resetForm = () => {
    form.stock_type = 'purchase'
    form.date = new Date().toISOString().split('T')[0]
    form.description = ''
    form.party_name = ''
    form.party_phone = ''
    form.party_address = ''
    form.items = []
    errors.value = {}
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

const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const submitForm = async () => {
    submitting.value = true
    errors.value = {}

    // Validate at least one item
    if (form.items.length === 0) {
        errors.value.items = ['Please add at least one product']
        submitting.value = false
        return
    }

    // Validate all items have product selected
    const invalidItems = form.items.filter(item => !item.product_id || item.quantity <= 0)
    if (invalidItems.length > 0) {
        errors.value.items = ['Please fill all product fields correctly']
        submitting.value = false
        return
    }

    // Prepare data for submission
    const submitData = {
        ...form,
        items: form.items.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        }))
    }

    try {
        const response = await axios.post('/stocks', submitData)

        if (response.data.success) {
            createdStock.value = response.data.data
            successMessage.value = `${formatStockType(form.stock_type)} created successfully!`
            showSuccessModal.value = true
        }
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}

            // Scroll to first error
            setTimeout(() => {
                const firstError = document.querySelector('.is-invalid')
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
            }, 100)
        } else {
            alert('An error occurred. Please try again.')
            console.error('Error:', error)
        }
    } finally {
        submitting.value = false
    }
}

const goBack = () => {
    router.back()
}

const handleModalClose = () => {
    showSuccessModal.value = false
    router.push({ name: 'stocks' })
}

const viewStock = () => {
    if (createdStock.value) {
        router.push({ name: 'stocks.show', params: { id: createdStock.value.id } })
    } else {
        router.push({ name: 'stocks' })
    }
}

const createAnother = () => {
    showSuccessModal.value = false
    resetForm()
    fetchProducts()

    // Scroll to top
    setTimeout(() => {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }, 100)
}

// Lifecycle
onMounted(() => {
    fetchProducts()
})
</script>

<style scoped>
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

/* Make readonly inputs look disabled but readable */
.bg-light {
    background-color: #f8f9fa !important;
    cursor: not-allowed;
}

/* Style for product options */
option:disabled {
    color: #6c757d;
    font-style: italic;
}
</style>
