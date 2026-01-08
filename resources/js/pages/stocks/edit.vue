<template>
    <div class="stock-creation-wrapper">
        <!-- Header -->
        <div class="header-section mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold text-dark">Edit Stock Entry</h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">
                        <CIcon name="cil-clock" class="me-1" />
                        {{ new Date().toLocaleDateString('en-PK') }}
                    </span>
                    <CButton color="light" @click="goBack">
                        <CIcon name="cil-arrow-left" class="me-1" />
                        Back
                    </CButton>
                </div>
            </div>
            <div class="text-muted mt-2">
                Reference: <strong>#{{ stock?.id || 'Loading...' }}</strong>
            </div>
        </div>

        <!-- Transaction Type Tabs -->
        <div class="transaction-type-tabs mb-4">
            <div class="d-flex gap-2">
                <CButton :color="form.stock_type === 'purchase' ? 'primary' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('purchase')">
                    <CIcon name="cil-cart" />
                    <span>Purchase</span>
                </CButton>
                <CButton :color="form.stock_type === 'sale' ? 'success' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('sale')">
                    <CIcon name="cil-dollar" />
                    <span>Sale</span>
                </CButton>
                <CButton :color="form.stock_type === 'issue' ? 'warning' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('issue')">
                    <CIcon name="cil-transfer" />
                    <span>Issue</span>
                </CButton>
                <CButton :color="form.stock_type === 'return' ? 'danger' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('return')">
                    <CIcon name="cil-loop" />
                    <span>Return</span>
                </CButton>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column - Customer/Supplier & Product Search -->
            <div class="col-lg-4">
                <!-- Customer/Supplier Card -->
                <CCard class="mb-4 shadow-sm">
                    <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">
                            <CIcon name="cil-user" class="me-2" />
                            {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }} Details
                        </h6>
                        <CButton color="link" size="sm" @click="showAdvanced = !showAdvanced">
                            <CIcon :name="showAdvanced ? 'cil-chevron-top' : 'cil-chevron-bottom'" class="me-1" />
                            {{ showAdvanced ? 'Hide' : 'Advanced' }}
                        </CButton>
                    </CCardHeader>
                    <CCardBody>
                        <!-- Basic Info -->
                        <div class="mb-3">
                            <CFormLabel class="fw-semibold">
                                {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }} Name
                            </CFormLabel>
                            <CFormInput v-model="form.party_name"
                                :placeholder="form.stock_type === 'sale' ? 'Enter customer name' : 'Enter supplier name'"
                                :invalid="errors.party_name" @input="clearError('party_name')" />
                            <CFormFeedback v-if="errors.party_name" invalid>
                                {{ errors.party_name[0] }}
                            </CFormFeedback>
                        </div>

                        <!-- Date -->
                        <div class="mb-3">
                            <CFormLabel class="fw-semibold">Date</CFormLabel>
                            <CFormInput type="date" v-model="form.date" :invalid="errors.date" required />
                            <CFormFeedback v-if="errors.date" invalid>
                                {{ errors.date[0] }}
                            </CFormFeedback>
                        </div>

                        <!-- Advanced Section (Collapsible) -->
                        <div v-if="showAdvanced" class="advanced-section mt-3 pt-3 border-top">
                            <h6 class="fw-semibold mb-3">Additional Information</h6>

                            <div class="mb-3">
                                <CFormLabel>Phone</CFormLabel>
                                <CFormInput v-model="form.party_phone" placeholder="Phone number" />
                            </div>

                            <div class="mb-3">
                                <CFormLabel>Address</CFormLabel>
                                <CFormInput v-model="form.party_address" placeholder="Address" />
                            </div>

                            <div class="mb-3">
                                <CFormLabel>Notes / Reference</CFormLabel>
                                <CTextarea v-model="form.description" rows="2" placeholder="Additional notes..." />
                            </div>
                        </div>
                    </CCardBody>
                </CCard>

                <!-- Product Search & Add Card -->
                <CCard class="shadow-sm">
                    <CCardHeader class="bg-light">
                        <h6 class="mb-0 fw-semibold">
                            <CIcon name="cil-plus" class="me-2" />
                            Add New Product
                        </h6>
                    </CCardHeader>
                    <CCardBody>
                        <!-- VAutocomplete for Product Selection -->
                        <div class="mb-3">
                            <CFormLabel class="fw-semibold">Select Product</CFormLabel>
                            <VAutocomplete v-model="selectedProduct" :items="availableProducts" item-title="name"
                                item-value="id"
                                :label="selectedProduct ? 'Change Product' : 'Search and select product'"
                                :placeholder="selectedProduct ? selectedProduct.name : 'Type to search products...'"
                                variant="outlined" density="compact" :rules="productRules" clearable return-object
                                @update:modelValue="handleProductSelect">
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:title>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ item.raw.name }}</span>
                                                <small class="text-muted">
                                                    SKU: {{ item.raw.sku || 'N/A' }} |
                                                    Stock: <span :class="getStockColor(item.raw)">{{
                                                        item.raw.current_stock || 0 }}</span> |
                                                    Unit: {{ item.raw.unit || 'N/A' }}
                                                </small>
                                            </div>
                                        </template>
                                        <template v-slot:append>
                                            <span class="text-primary fw-bold">
                                                PKR {{ formatCurrency(getProductPrice(item.raw)) }}
                                            </span>
                                        </template>
                                    </v-list-item>
                                </template>
                                <template v-slot:selection="{ item }">
                                    <span class="fw-semibold">{{ item.raw.name }}</span>
                                </template>
                            </VAutocomplete>
                            <CFormFeedback v-if="productError" invalid>
                                {{ productError }}
                            </CFormFeedback>
                        </div>

                        <!-- Quantity Input -->
                        <div class="mb-3">
                            <CFormLabel class="fw-semibold">Quantity</CFormLabel>
                            <CInputGroup>
                                <CFormInput type="number" v-model.number="editQuantity" placeholder="Enter quantity"
                                    :invalid="quantityError" @input="handleQuantityChange" />
                                <CInputGroupText v-if="selectedProduct?.unit">
                                    {{ selectedProduct.unit }}
                                </CInputGroupText>
                            </CInputGroup>
                            <CFormFeedback v-if="quantityError" invalid>
                                {{ quantityError }}
                            </CFormFeedback>
                        </div>

                        <!-- Price and Total -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <CFormLabel>Unit Price</CFormLabel>
                                <CInputGroup>
                                    <CInputGroupText>PKR</CInputGroupText>
                                    <CFormInput :value="selectedProduct ? getProductPrice(selectedProduct) : '0.00'"
                                        readonly class="bg-light" />
                                </CInputGroup>
                            </div>
                            <div class="col-6">
                                <CFormLabel>Total</CFormLabel>
                                <div class="h5 text-primary fw-bold">
                                    PKR {{ formatCurrency(editTotal) }}
                                </div>
                            </div>
                        </div>

                        <!-- Add/Update Button -->
                        <div class="d-grid gap-2">
                            <CButton color="primary" @click="addOrUpdateProduct" :disabled="!canAddProduct"
                                class="py-2">
                                <CIcon name="cil-check" class="me-2" />
                                {{ isEditingExisting ? 'Update Product' : 'Add to List' }}
                            </CButton>
                            <CButton v-if="selectedProduct" color="light" @click="clearSelectedProduct" size="sm">
                                <CIcon name="cil-x" class="me-1" />
                                Clear Selection
                            </CButton>
                        </div>
                    </CCardBody>
                </CCard>

                <!-- Quick Stock Info -->
                <CCard class="shadow-sm mt-3">
                    <CCardBody class="py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Total Items:</small>
                            <span class="badge bg-primary">{{ form.items.length }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted">Grand Total:</small>
                            <span class="fw-bold text-primary">PKR {{ formatCurrency(totalAmount) }}</span>
                        </div>
                    </CCardBody>
                </CCard>
            </div>

            <!-- Right Column - Product List & Actions -->
            <div class="col-lg-8">
                <!-- Products List Card -->
                <CCard class="shadow-sm h-100">
                    <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">
                            <CIcon name="cil-list" class="me-2" />
                            Product List
                            <span class="badge bg-primary ms-2">{{ form.items.length }}</span>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted me-3">
                                Total: <strong class="text-primary">PKR {{ formatCurrency(totalAmount) }}</strong>
                            </small>
                            <CButton color="light" size="sm" @click="clearAllProducts"
                                :disabled="form.items.length === 0">
                                <CIcon name="cil-trash" class="me-1" />
                                Clear All
                            </CButton>
                        </div>
                    </CCardHeader>
                    <CCardBody class="p-0">
                        <!-- Empty State -->
                        <div v-if="form.items.length === 0" class="empty-state text-center py-5">
                            <div class="empty-icon mb-3">
                                <CIcon name="cil-cart" size="3xl" class="text-muted" />
                            </div>
                            <h5 class="text-muted mb-2">No Products Added</h5>
                            <p class="text-muted">Select products from the left panel to add them here</p>
                        </div>

                        <!-- Products Table -->
                        <div v-else class="products-table">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="35%">Product</th>
                                            <th width="10%" class="text-center">Stock</th>
                                            <th width="15%" class="text-center">Quantity</th>
                                            <th width="15%" class="text-center">Unit Price</th>
                                            <th width="15%" class="text-center">Total</th>
                                            <th width="5%" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in form.items" :key="index"
                                            :class="{ 'table-warning': isLowStock(item) }">
                                            <td class="align-middle">
                                                <span class="badge bg-light text-dark">{{ index + 1 }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="fw-semibold">{{ getProductName(item.product_id) }}</div>
                                                <small class="text-muted">
                                                    {{ getProductSKU(item.product_id) }} |
                                                    {{ getProductUnit(item.product_id) }}
                                                </small>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge" :class="getStockBadgeClass(item)">
                                                    {{ getProductStock(item.product_id) }}
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="mx-2">{{ item.quantity }}</span>
                                                    <small class="text-muted ms-1">{{ getProductUnit(item.product_id)
                                                    }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="text-muted small">PKR</div>
                                                <div class="fw-semibold">{{
                                                    formatCurrency(getUnitPrice(item.product_id)) }}</div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="fw-bold text-primary">
                                                    PKR {{ formatCurrency(item.total) }}
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <CButton color="light" size="sm"
                                                        @click="editExistingProduct(index)">
                                                        <CIcon name="cil-pencil" />
                                                    </CButton>
                                                    <CButton color="light" size="sm" @click="removeProduct(index)">
                                                        <CIcon name="cil-trash" />
                                                    </CButton>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="5" class="text-end fw-bold h5">Total Amount:</td>
                                            <td colspan="2" class="text-start fw-bold h5 text-primary">
                                                PKR {{ formatCurrency(totalAmount) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>

                <!-- Actions Card -->
                <CCard class="mt-4 shadow-sm">
                    <CCardBody>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <CButton color="warning" @click="resetToOriginal" :disabled="!hasChanges">
                                    <CIcon name="cil-reload" class="me-2" />
                                    {{ hasChanges ? 'Reset Changes' : 'No Changes' }}
                                </CButton>
                                <small class="text-muted d-block mt-1">
                                    Reset to original values
                                </small>
                            </div>
                            <div class="d-flex gap-3">
                                <CButton color="light" @click="printReceipt" :disabled="form.items.length === 0">
                                    <CIcon name="cil-print" class="me-2" />
                                    Print Preview
                                </CButton>
                                <CButton color="secondary" @click="goBack">
                                    <CIcon name="cil-x" class="me-2" />
                                    Cancel
                                </CButton>
                                <CButton color="primary" @click="submitForm"
                                    :disabled="submitting || form.items.length === 0 || !hasChanges">
                                    <CSpinner v-if="submitting" component="span" size="sm" class="me-2" />
                                    <CIcon v-else name="cil-save" class="me-2" />
                                    {{ submitting ? 'Updating...' : 'Update Record' }}
                                </CButton>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
        </div>

        <!-- Success Modal -->
        <CModal :visible="showSuccessModal" @close="handleModalClose">
            <CModalHeader class="border-0">
                <CModalTitle class="text-success">
                    <CIcon name="cil-check-circle" class="me-2" />
                    Success!
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="text-center py-3">
                    <h5 class="mb-3">Stock Entry Updated Successfully!</h5>
                    <p class="text-muted">{{ successMessage }}</p>

                    <div class="alert alert-light mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Reference</small>
                                <strong>#{{ updatedStock?.id || stock?.id }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total Amount</small>
                                <strong class="text-primary">PKR {{ formatCurrency(updatedStock?.net_price ||
                                    totalAmount) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter class="border-0">
                <div class="d-flex justify-content-between w-100">
                    <CButton color="light" @click="printReceipt">
                        <CIcon name="cil-print" class="me-2" />
                        Print
                    </CButton>
                    <div class="d-flex gap-2">
                        <CButton color="secondary" @click="handleModalClose">
                            Close
                        </CButton>
                        <CButton color="primary" @click="viewStock">
                            <CIcon name="cil-eye" class="me-2" />
                            View Details
                        </CButton>
                    </div>
                </div>
            </CModalFooter>
        </CModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

// State
const stock = ref(null)
const availableProducts = ref([])
const submitting = ref(false)
const loading = ref(true)
const showSuccessModal = ref(false)
const showAdvanced = ref(false)
const errors = ref({})

// VAutocomplete state
const selectedProduct = ref(null)
const editQuantity = ref(null)
const editTotal = ref(0)
const isEditingExisting = ref(false)
const editingIndex = ref(-1)
const productError = ref('')
const quantityError = ref('')

// Form data
const form = reactive({
    stock_type: 'purchase',
    date: new Date().toISOString().split('T')[0],
    description: '',
    party_name: '',
    party_phone: '',
    party_address: '',
    items: []
})

const successMessage = ref('')
const updatedStock = ref(null)
const originalFormData = ref(null)

// Computed properties
const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.total || 0), 0)
})

const hasChanges = computed(() => {
    if (!originalFormData.value) return false
    return JSON.stringify(form) !== JSON.stringify(originalFormData.value)
})

const canAddProduct = computed(() => {
    return selectedProduct.value && editQuantity.value > 0 && !quantityError.value
})

const productRules = [
    (v) => !!v || 'Product is required',
    (v) => {
        if (!v) return true
        // Check if product is already in the list (except when editing)
        if (!isEditingExisting.value) {
            const exists = form.items.some(item => item.product_id == v.id)
            if (exists) return 'Product already added to list'
        }
        return true
    }
]

// Methods
const fetchStock = async () => {
    try {
        loading.value = true
        const response = await axios.get(`/stocks/${route.params.id}`)
        if (response.data.success) {
            stock.value = response.data.data

            // Populate form
            form.stock_type = stock.value.stock_type || 'purchase'
            form.date = stock.value.date
                ? new Date(stock.value.date).toISOString().split('T')[0]
                : new Date().toISOString().split('T')[0]
            form.description = stock.value.description || ''
            form.party_name = stock.value.party_name || ''
            form.party_phone = stock.value.party_phone || ''
            form.party_address = stock.value.party_address || ''

            // Populate items
            if (stock.value.items && Array.isArray(stock.value.items)) {
                form.items = stock.value.items.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    total: item.total_price || (item.quantity * item.unit_price) || 0
                }))
            }

            // Store original data for reset
            originalFormData.value = JSON.parse(JSON.stringify(form))
        }
    } catch (error) {
        console.error('Error fetching stock:', error)
        Swal.fire({
            title: 'Load Failed',
            text: 'Failed to load stock entry. Please try again.',
            icon: 'error'
        }).then(() => {
            router.push({ name: 'stocks' })
        })
    } finally {
        loading.value = false
    }
}

const fetchProducts = async () => {
    try {
        const response = await axios.get('/active-products')
        console.log('products data', response.data);
        availableProducts.value = response.data || [];
    } catch (error) {
        console.error('Error fetching products:', error)
    }
}

const setTransactionType = (type) => {
    form.stock_type = type
    // Update prices for existing items
    form.items.forEach((item, index) => {
        if (item.product_id) {
            updateItemTotal(item.product_id, index)
        }
    })
}

const handleProductSelect = (product) => {
    if (!product) {
        clearSelectedProduct()
        return
    }

    selectedProduct.value = product
    productError.value = ''

    // Check if product is already in the list
    const existingIndex = form.items.findIndex(item => item.product_id == product.id)

    if (existingIndex > -1) {
        const existingItem = form.items[existingIndex]
        editQuantity.value = existingItem.quantity || 1
        isEditingExisting.value = true
        editingIndex.value = existingIndex

        // Show info message
        productError.value = 'Product already in list. Updating will replace existing entry.'
    } else {
        editQuantity.value = 1
        isEditingExisting.value = false
        editingIndex.value = -1
    }

    updateEditTotal()
    validateQuantity()
}

const handleQuantityChange = () => {
    validateQuantity()
    updateEditTotal()
}

const validateQuantity = () => {
    quantityError.value = ''

    if (!selectedProduct.value) {
        quantityError.value = 'Please select a product first'
        return false
    }

    const qty = Number(editQuantity.value)

    if (isNaN(qty) || qty <= 0) {
        quantityError.value = 'Quantity must be greater than 0'
        return false
    }

    // Stock validation for sales/issue
    if (form.stock_type === 'sale' || form.stock_type === 'issue') {
        const availableStock = selectedProduct.value.current_stock || 0
        if (qty > availableStock && !isEditingExisting.value) {
            quantityError.value = `Insufficient stock. Available: ${availableStock}`
            return false
        }
    }

    return true
}

const clearSelectedProduct = () => {
    selectedProduct.value = null
    editQuantity.value = 1
    editTotal.value = 0
    isEditingExisting.value = false
    editingIndex.value = -1
    productError.value = ''
    quantityError.value = ''
}

const updateEditTotal = () => {
    if (!selectedProduct.value || !validateQuantity()) {
        editTotal.value = 0
        return
    }

    const qty = Number(editQuantity.value) || 1
    const price = getProductPrice(selectedProduct.value)
    editTotal.value = Math.round(qty * price * 100) / 100
}

const updateItemTotal = (productId, index) => {
    if (index >= 0 && index < form.items.length) {
        const item = form.items[index]
        const product = availableProducts.value.find(p => p.id == productId)
        if (product) {
            const price = getProductPrice(product)
            const total = Math.round(item.quantity * price * 100) / 100
            form.items[index].total = total
        }
    }
}

const getProductPrice = (product) => {
    if (!product) return 0
    const priceField = form.stock_type === 'sale' ? 'sale_price' : 'purchase_price'
    const rawPrice = product[priceField]
    const parsed = parseFloat(rawPrice)
    return isNaN(parsed) ? 0 : parsed
}

const addOrUpdateProduct = () => {
    // Validate inputs
    if (!selectedProduct.value) {
        productError.value = 'Please select a product'
        return
    }

    if (!validateQuantity()) {
        return
    }

    const qty = Number(editQuantity.value)
    const price = getProductPrice(selectedProduct.value)
    const total = Math.round(qty * price * 100) / 100

    const productItem = {
        product_id: selectedProduct.value.id,
        quantity: qty,
        total: total
    }

    if (isEditingExisting.value && editingIndex.value > -1) {
        // Update existing item
        form.items[editingIndex.value] = productItem
    } else {
        // Add new item
        form.items.push(productItem)
    }

    clearSelectedProduct()
}

const editExistingProduct = (index) => {
    const item = form.items[index]
    const product = availableProducts.value.find(p => p.id == item.product_id)

    if (product) {
        selectedProduct.value = product
        editQuantity.value = item.quantity
        isEditingExisting.value = true
        editingIndex.value = index

        // Scroll to product selection section
        nextTick(() => {
            const productSection = document.querySelector('.v-autocomplete')
            if (productSection) {
                productSection.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        })
    } else {
        Swal.fire({
            title: 'Product Not Found',
            text: 'Product not found in available products list.',
            icon: 'warning'
        })
    }
}

const removeProduct = async (index) => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to remove this product?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    })

    if (result.isConfirmed) {
        form.items.splice(index, 1)
        Swal.fire({
            title: 'Removed!',
            text: 'Product has been removed.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        })
    }
}


const clearAllProducts = async () => {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to remove all products?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear all',
        cancelButtonText: 'Cancel'
    })

    if (result.isConfirmed) {
        form.items = []
        Swal.fire({
            title: 'Cleared!',
            text: 'All products have been removed.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        })
    }
}


// Helper methods
const getProductName = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return product ? product.name : 'Unknown Product'
}

const getProductSKU = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return product ? product.sku : 'N/A'
}

const getProductStock = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return product ? (product.current_stock || 0) : 0
}

const getProductUnit = (productId) => {
    const product = availableProducts.value.find(p => p.id == productId)
    return product ? product.unit : ''
}

const getUnitPrice = (productId) => {
    if (!productId) return 0
    const product = availableProducts.value.find(p => p.id == productId)
    if (!product) return 0
    return form.stock_type === 'sale'
        ? parseFloat(product.sale_price) || 0
        : parseFloat(product.purchase_price) || 0
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

const getStockColor = (product) => {
    if (!product) return 'text-muted'
    const stock = product.current_stock || 0
    if (stock <= 0) return 'text-danger'
    if (stock <= 10) return 'text-warning'
    return 'text-success'
}

const isLowStock = (item) => {
    const stock = getProductStock(item.product_id)
    return stock <= 10
}

const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field]
    }
}

const resetToOriginal = () => {
    if (originalFormData.value && confirm('Reset all changes to original values?')) {
        Object.assign(form, JSON.parse(JSON.stringify(originalFormData.value)))
        clearSelectedProduct()
    }
}

const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const printReceipt = () => {
    const printWindow = window.open('', '_blank')
    const now = new Date()

    const itemsHtml = form.items.map((item, index) => {
        const product = availableProducts.value.find(p => p.id == item.product_id)
        const productName = product ? product.name : 'Unknown Product'
        let displayName = productName
        if (displayName.length > 18) {
            displayName = displayName.substring(0, 15) + '...'
        }

        return `<tr><td class="col-desc">${displayName}</td><td class="col-qty">${item.quantity}</td><td class="col-price">${formatCurrency(getUnitPrice(item.product_id))}</td><td class="col-total">${formatCurrency(item.total)}</td></tr>`
    }).join('')

    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 11px;
                    width: 80mm;
                    margin: 0 auto;
                    padding: 5px;
                    line-height: 1.2;
                }
                .center { text-align: center; }
                .store-header {
                    font-weight: bold;
                    margin-bottom: 5px;
                    border-bottom: 1px dashed #000;
                    padding-bottom: 5px;
                }
                .store-name { font-size: 14px; text-transform: uppercase; }
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 8px 0;
                    font-size: 10px;
                    table-layout: fixed;
                }
                .col-desc { width: 106px; text-align: left; padding: 0 2px; }
                .col-qty { width: 40px; text-align: left; }
                .col-price { width: 40px; text-align: right; padding-right: 5px; }
                .col-total { width: 40px; text-align: right; }
                .total-section { border-top: 2px solid #000; margin-top: 10px; padding-top: 5px; }
                .total-row { display: flex; justify-content: space-between; margin: 2px 0; }
                .grand-total { font-size: 12px; font-weight: bold; }
                @media print {
                    body { width: 80mm !important; margin: 0; padding: 2mm; }
                }
            </style>
        </head>
        <body onload="window.print(); setTimeout(() => window.close(), 500)">
            <div class="center">
                <div class="store-header">
                    <div class="store-name">SHAHZAIB ELECTRIC STORE</div>
                    <div style="font-size: 10px;">📞 0308-8840832 | Lahore</div>
                </div>
                <div style="margin: 5px 0; font-weight: bold;">${form.stock_type.toUpperCase()} RECEIPT</div>
                <div style="font-size: 10px;">
                    <div>Date: ${new Date(form.date).toLocaleDateString('en-PK')}</div>
                    <div>Ref: #${stock?.id || 'EDIT-PREVIEW'}</div>
                    ${form.party_name ? `<div>${form.stock_type === 'sale' ? 'Customer' : 'Supplier'}: ${form.party_name}</div>` : ''}
                </div>
                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">
                <div style="font-weight: bold; margin: 5px 0;">ITEMS (${form.items.length})</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="col-desc">Product</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-price">Price</th>
                            <th class="col-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">
                <div class="total-section">
                    <div class="total-row grand-total">
                        <span>TOTAL:</span>
                        <span>PKR ${formatCurrency(totalAmount.value)}</span>
                    </div>
                </div>
                <div style="font-size: 9px; margin-top: 15px; padding-top: 5px; border-top: 1px dashed #000;">
                    <div>Printed: ${now.toLocaleDateString()} ${now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                    <div style="margin-top: 5px;">Thank you for your business!</div>
                    <div style="font-size: 8px; margin-top: 3px; color: #999;">[Edit Preview]</div>
                </div>
            </div>
        </body>
        </html>
    `

    printWindow.document.write(printContent)
    printWindow.document.close()
}

const submitForm = async () => {
    submitting.value = true
    errors.value = {}
    productError.value = ''
    quantityError.value = ''

    // Validate form
    if (form.items.length === 0) {
        errors.value.items = ['Please add at least one product']
        submitting.value = false
        return
    }

    // Check for duplicate products
    const productIds = form.items.map(item => item.product_id)
    const uniqueIds = [...new Set(productIds)]
    if (productIds.length !== uniqueIds.length) {
        Swal.fire({
            title: 'Duplicate Products',
            text: 'Error: Duplicate products found in the list.',
            icon: 'error'
        })
        submitting.value = false
        return
    }

    const submitData = {
        ...form,
        items: form.items.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        }))
    }

    try {
        const response = await axios.put(`/stocks/${route.params.id}`, submitData)

        if (response.data.success) {
            updatedStock.value = response.data.data
            successMessage.value = `${form.stock_type.charAt(0).toUpperCase() + form.stock_type.slice(1)} updated successfully!`
            showSuccessModal.value = true
            originalFormData.value = JSON.parse(JSON.stringify(form))
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
            Swal.fire({
                title: 'Error',
                text: 'Do not add duplicate entries. Please try again.',
                icon: 'error'
            })
            console.error('Error:', error)
        }
    } finally {
        submitting.value = false
    }
}

const goBack = async () => {
    if (hasChanges.value) {
        const result = await Swal.fire({
            title: 'Unsaved Changes',
            text: 'You have unsaved changes. Are you sure you want to leave?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, leave',
            cancelButtonText: 'Stay'
        })

        if (result.isConfirmed) {
            router.back()
        }
    } else {
        router.back()
    }
}

const handleModalClose = () => {
    showSuccessModal.value = false
    router.push({ name: 'stocks' })
}

const viewStock = () => {
    if (updatedStock.value) {
        router.push({ name: 'stocks.show', params: { id: updatedStock.value.id } })
    } else {
        router.push({ name: 'stocks.show', params: { id: route.params.id } })
    }
}

// Lifecycle
onMounted(async () => {
    await Promise.all([
        fetchStock(),
        fetchProducts()
    ])
})

// Watch for quantity changes
watch(editQuantity, (newVal) => {
    let sanitized = Number(newVal) || 0
    if (newVal !== sanitized) {
        editQuantity.value = sanitized
    }
    validateQuantity()
    updateEditTotal()
}, { immediate: true })

// Watch for stock type changes
watch(() => form.stock_type, () => {
    if (selectedProduct.value) {
        updateEditTotal()
    }
})
</script>

<style scoped>
.stock-creation-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.header-section {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
}

.transaction-type-tabs .btn {
    min-width: 120px;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.transaction-type-tabs .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.transaction-type-tabs .btn-primary {
    border-color: var(--cui-primary, #321fdb);
}

.transaction-type-tabs .btn-success {
    border-color: var(--cui-success, #198754);
}

.transaction-type-tabs .btn-warning {
    border-color: var(--cui-warning, #f9b115);
}

.transaction-type-tabs .btn-danger {
    border-color: var(--cui-danger, #e55353);
}

.advanced-section {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.cursor-pointer {
    cursor: pointer;
}

.empty-state {
    color: #6c757d;
}

.empty-icon {
    opacity: 0.5;
}

.products-table {
    max-height: 500px;
    overflow-y: auto;
}

.products-table table {
    margin-bottom: 0;
}

.products-table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 10;
    border-bottom: 2px solid #dee2e6;
}

.products-table tfoot {
    position: sticky;
    bottom: 0;
    background: #f8f9fa;
    z-index: 10;
    border-top: 2px solid #dee2e6;
}

/* Vuetify autocomplete custom styles */
:deep(.v-autocomplete .v-field) {
    background: white;
    border-radius: 4px;
}

:deep(.v-autocomplete .v-field--focused) {
    border-color: #321fdb;
}

:deep(.v-list-item) {
    min-height: 48px;
}

:deep(.v-list-item__content) {
    padding: 4px 0;
}

/* Responsive design */
@media (max-width: 992px) {
    .transaction-type-tabs .btn {
        min-width: 100px;
        padding: 8px 12px;
    }
}

@media (max-width: 768px) {
    .stock-creation-wrapper {
        padding: 10px;
    }

    .transaction-type-tabs {
        overflow-x: auto;
        padding-bottom: 10px;
    }

    .transaction-type-tabs .btn {
        min-width: auto;
        flex-shrink: 0;
    }

    .col-lg-4,
    .col-lg-8 {
        width: 100%;
    }
}
</style>
