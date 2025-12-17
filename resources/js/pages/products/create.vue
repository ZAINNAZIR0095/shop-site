<!-- resources/js/views/products/CreateProduct.vue -->
<template>
  <div>
    <!-- Breadcrumb -->
    <CBreadcrumb class="my-3">
      <CBreadcrumbItem :to="{ name: 'products' }">Products</CBreadcrumbItem>
      <CBreadcrumbItem active>Create Product</CBreadcrumbItem>
    </CBreadcrumb>

    <!-- Form Card -->
    <CCard>
      <CCardHeader>
        <strong>Create New Product</strong>
      </CCardHeader>
      <CCardBody>
        <CForm @submit.prevent="submitForm">
          <!-- Name Field -->
          <div class="mb-3">
            <CFormLabel for="name">Product Name <span class="text-danger">*</span></CFormLabel>
            <CFormInput
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Enter product name"
              :invalid="errors.name"
              @input="clearError('name')"
            />
            <CFormFeedback invalid v-if="errors.name">
              {{ errors.name[0] }}
            </CFormFeedback>
          </div>

          <!-- Type Field -->
          <div class="mb-3">
            <CFormLabel for="type">Product Type <span class="text-danger">*</span></CFormLabel>
            <CFormSelect
              id="type"
              v-model="form.type"
              :invalid="errors.type"
              @change="clearError('type')"
            >
              <option value="">Select Type</option>
              <option value="physical">Physical Product</option>
              <option value="digital">Digital Product</option>
              <option value="service">Service</option>
            </CFormSelect>
            <CFormFeedback invalid v-if="errors.type">
              {{ errors.type[0] }}
            </CFormFeedback>
          </div>

          <div class="row">
            <!-- Unit Field -->
            <div class="col-md-6 mb-3">
              <CFormLabel for="unit">Unit</CFormLabel>
              <CFormInput
                id="unit"
                v-model="form.unit"
                type="text"
                placeholder="e.g., kg, liter, piece"
                :invalid="errors.unit"
                @input="clearError('unit')"
              />
              <CFormFeedback invalid v-if="errors.unit">
                {{ errors.unit[0] }}
              </CFormFeedback>
            </div>

            <!-- Size Field -->
            <div class="col-md-6 mb-3">
              <CFormLabel for="size">Size</CFormLabel>
              <CFormInput
                id="size"
                v-model="form.size"
                type="text"
                placeholder="e.g., 500ml, 1kg, 10x10cm"
                :invalid="errors.size"
                @input="clearError('size')"
              />
              <CFormFeedback invalid v-if="errors.size">
                {{ errors.size[0] }}
              </CFormFeedback>
            </div>
          </div>

          <!-- Minimum Limit Field -->
          <div class="mb-3">
            <CFormLabel for="min_limit">Minimum Stock Limit <span class="text-danger">*</span></CFormLabel>
            <CFormInput
              id="min_limit"
              v-model="form.min_limit"
              type="number"
              min="0"
              placeholder="Enter minimum stock limit"
              :invalid="errors.min_limit"
              @input="clearError('min_limit')"
            />
            <CFormText>Alert when stock goes below this limit</CFormText>
            <CFormFeedback invalid v-if="errors.min_limit">
              {{ errors.min_limit[0] }}
            </CFormFeedback>
          </div>

          <div class="row">
            <!-- Purchase Price -->
            <div class="col-md-6 mb-3">
              <CFormLabel for="purchase_price">Purchase Price <span class="text-danger">*</span></CFormLabel>
              <CInputGroup>
                <CInputGroupText>PKR</CInputGroupText>
                <CFormInput
                  id="purchase_price"
                  v-model="form.purchase_price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  :invalid="errors.purchase_price"
                  @input="clearError('purchase_price'); calculateProfit()"
                />
              </CInputGroup>
              <CFormFeedback invalid v-if="errors.purchase_price">
                {{ errors.purchase_price[0] }}
              </CFormFeedback>
            </div>

            <!-- Sale Price -->
            <div class="col-md-6 mb-3">
              <CFormLabel for="sale_price">Sale Price <span class="text-danger">*</span></CFormLabel>
              <CInputGroup>
                <CInputGroupText>PKR</CInputGroupText>
                <CFormInput
                  id="sale_price"
                  v-model="form.sale_price"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  :invalid="errors.sale_price"
                  @input="clearError('sale_price'); calculateProfit()"
                />
              </CInputGroup>
              <CFormFeedback invalid v-if="errors.sale_price">
                {{ errors.sale_price[0] }}
              </CFormFeedback>
            </div>
          </div>

          <!-- Profit Calculation -->
          <div v-if="showProfit" class="alert alert-info">
            <strong>Profit:</strong> PKR{{ profit }} ({{ profitPercentage }}%)
          </div>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between mt-4">
            <CButton color="secondary" @click="goBack">
              <CIcon name="cil-arrow-left" /> Back
            </CButton>
            <div>
              <CButton color="light" class="me-2" @click="resetForm">
                <CIcon name="cil-reload" /> Reset
              </CButton>
              <CButton type="submit" color="primary" :disabled="loading">
                <CSpinner v-if="loading" component="span" size="sm" aria-hidden="true" />
                <CIcon v-else name="cil-save" />
                {{ loading ? 'Saving...' : 'Save Product' }}
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
          <h5>Product Created Successfully!</h5>
          <p class="mb-0">{{ successMessage }}</p>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="primary" @click="redirectToList">Go to Products List</CButton>
        <CButton color="secondary" @click="createAnother">Create Another</CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

// CoreUI Components
import {
  CCard, CCardHeader, CCardBody,
  CForm, CFormLabel, CFormInput, CFormSelect, CFormText, CFormFeedback,
  CButton, CSpinner,
  CInputGroup, CInputGroupText,
  CBreadcrumb, CBreadcrumbItem,
  CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter,
} from '@coreui/vue'


import { cilArrowLeft, cilReload, cilSave, cilCheckCircle } from '@coreui/icons'

const router = useRouter()
const loading = ref(false)
const showSuccessModal = ref(false)
const successMessage = ref('')
const errors = ref({})
const createdProductId = ref(null)

// Form data
const form = reactive({
  name: '',
  type: '',
  unit: '',
  size: '',
  min_limit: 0,
  purchase_price: 0,
  sale_price: 0
})

// Calculate profit
const profit = computed(() => {
  const purchase = parseFloat(form.purchase_price) || 0
  const sale = parseFloat(form.sale_price) || 0
  return (sale - purchase).toFixed(2)
})

const profitPercentage = computed(() => {
  const purchase = parseFloat(form.purchase_price) || 0
  const profitValue = parseFloat(profit.value) || 0
  if (purchase === 0) return 0
  return ((profitValue / purchase) * 100).toFixed(2)
})

const showProfit = computed(() => {
  return form.purchase_price > 0 && form.sale_price > 0
})

// Methods
const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

const calculateProfit = () => {
  // Trigger computed properties
  profit.value
  profitPercentage.value
}

const resetForm = () => {
  Object.assign(form, {
    name: '',
    type: '',
    unit: '',
    size: '',
    min_limit: 0,
    purchase_price: 0,
    sale_price: 0
  })
  errors.value = {}
  createdProductId.value = null
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  console.log('Submitting form data:', form)

  try {
    // FIXED: Use correct API endpoint
    const response = await axios.post('/products', form)
    console.log('API Response:', response.data)

    // Handle success - check different response structures
    if (response.status === 201 || response.data.success === true || response.data.id) {
      createdProductId.value = response.data.data?.id || response.data.id

      // Get product name from response or form
      const productName = response.data.data?.name || response.data.name || form.name
      successMessage.value = `Product "${productName}" has been created successfully.`

      showSuccessModal.value = true
    } else {
      // If response doesn't indicate success
      console.warn('Unexpected response structure:', response.data)
      successMessage.value = 'Product created (check response)'
      showSuccessModal.value = true
    }
  } catch (error) {
    console.error('API Error:', error)
    console.error('Error Response:', error.response?.data)

    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors || {}
      alert('Please fix the validation errors above.')
    } else if (error.response && error.response.status === 500) {
      alert('Server error. Please check Laravel logs.')
    } else {
      alert(`An error occurred: ${error.message}`)
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const handleModalClose = () => {
  showSuccessModal.value = false
  // Don't redirect automatically when modal is closed via X button
}

const redirectToList = () => {
  showSuccessModal.value = false
  router.push({ name: 'products' })
}

const createAnother = () => {
  showSuccessModal.value = false
  resetForm()

  // Scroll to top after a small delay to ensure modal is closed
  setTimeout(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }, 100)

  // Optional: Focus on first input field
  setTimeout(() => {
    document.getElementById('name')?.focus()
  }, 200)
}
</script>

<style scoped>
.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}
</style>
