<template>
    <div class="stock-creation-wrapper">
        <!-- Header -->
        <div class="header-section mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold text-dark">Create Stock Entry</h1>
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
        </div>

        <!-- Transaction Type Tabs -->
        <div class="transaction-type-tabs mb-4">
            <div class="d-flex gap-2">
                <CButton :color="form.stock_type === 'sale' ? 'success' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('sale')">
                    <CIcon name="cil-dollar" />
                    <span>Sale</span>
                </CButton>
                <CButton :color="form.stock_type === 'purchase' ? 'primary' : 'light'"
                    class="px-4 py-3 d-flex align-items-center gap-2" @click="setTransactionType('purchase')">
                    <CIcon name="cil-cart" />
                    <span>Purchase</span>
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
                        <!-- In the Customer/Supplier Card, replace or modify the party selection section -->
                        <div class="mb-3">
                            <CFormLabel class="fw-semibold">
                                {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }} Name
                                <span class="text-danger">*</span>
                            </CFormLabel>
                            <CInputGroup>
                                <VAutocomplete v-model="form.party_name" :items="parties" item-title="name"
                                    item-value="name" :label="form.stock_type === 'sale' ? 'Customer' : 'Supplier'"
                                    :placeholder="form.stock_type === 'sale' ? 'Select customer' : 'Select supplier'"
                                    :error="!!errors.party_name" :error-messages="errors.party_name" clearable
                                    @update:modelValue="handlePartyChange" />
                                <CButton color="light" @click="showPartySearch = true" title="Search">
                                    <CIcon name="cil-search" />
                                </CButton>
                            </CInputGroup>
                            <CFormFeedback v-if="errors.party_name" invalid>
                                {{ errors.party_name[0] }}
                            </CFormFeedback>

                            <!-- Show balance information -->
                            <div v-if="selectedParty" class="mt-2">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Previous Balance:</span>
                                    <span :class="getBalanceClass(previousBalance)">
                                        PKR {{ formatCurrency(Math.abs(previousBalance)) }}
                                        <span v-if="previousBalance !== 0">
                                            ({{ previousBalance > 0 ? 'Owes' : 'Credit' }})
                                        </span>
                                    </span>
                                </div>

                            </div>
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
                                <CFormLabel>Email</CFormLabel>
                                <CFormInput v-model="form.party_email" type="email" placeholder="Email address" />
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
                            <CIcon name="cil-search" class="me-2" />
                            Add Products
                        </h6>
                    </CCardHeader>
                    <CCardBody>
                        <!-- Search Bar -->
                        <div class="mb-3">
                            <!-- <CInputGroup>
                                <CInputGroupText>
                                    <CIcon name="cil-search" />
                                </CInputGroupText>
                                <CFormInput v-model="searchQuery" placeholder="Search product by name or SKU..."
                                    @keyup.enter="searchProducts" ref="searchInput" />
                                <CButton color="primary" @click="searchProducts">
                                    Search
                                </CButton>
                            </CInputGroup> -->
                            <VAutocomplete v-model="selectedProduct" :items="availableProducts" item-title="name"
                                item-value="id" label="Select Product" return-object />
                        </div>

                        <!-- Quick Actions -->
                        <div class="mb-3">
                            <div class="d-flex flex-wrap gap-2">
                                <CButton color="light" size="sm" @click="showAllProducts = !showAllProducts">
                                    <CIcon name="cil-list" class="me-1" />
                                    {{ showAllProducts ? 'Hide All' : 'Show All' }}
                                </CButton>
                                <CButton color="light" size="sm" @click="clearSearch">
                                    <CIcon name="cil-reload" class="me-1" />
                                    Clear
                                </CButton>
                            </div>
                        </div>

                        <!-- Search Results / All Products -->
                        <div v-if="showAllProducts || searchResults.length > 0" class="product-search-results">
                            <div class="search-header d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    {{ showAllProducts ? 'All Products' : 'Search Results' }}
                                    ({{ filteredProducts.length }})
                                </small>
                                <small class="text-muted">Press Enter to add</small>
                            </div>

                            <div class="list-group product-list" style="max-height: 400px; overflow-y: auto;">
                                <div v-for="product in filteredProducts" :key="product.id"
                                    class="list-group-item list-group-item-action cursor-pointer"
                                    @click="selectProductForEdit(product)" @keyup.enter="selectProductForEdit(product)">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-semibold">{{ product.name }}</div>
                                            <small class="text-muted">
                                                SKU: {{ product.sku || 'N/A' }} |
                                                Stock: <span :class="getStockColor(product)">{{ product.current_stock ||
                                                    0 }}</span>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-primary">
                                                PKR {{ formatCurrency(getProductPrice(product)) }}
                                            </div>
                                            <small class="text-muted">{{ product.unit || 'unit' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Product Edit Form -->
                        <div v-if="selectedProduct" class="product-edit-form mt-4 p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Edit Product</h6>
                                <CButton color="light" size="sm" @click="clearSelectedProduct">
                                    <CIcon name="cil-x" />
                                </CButton>
                            </div>

                            <div class="mb-3">
                                <CFormLabel>Product</CFormLabel>
                                <div class="form-control bg-light">
                                    {{ selectedProduct.name }}
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <CFormLabel>Quantity</CFormLabel>
                                    <CFormInput type="number" v-model.number="editQuantity" min="1" step="1"
                                        @input="updateEditTotal"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                </div>
                                <div class="col-6">
                                    <CFormLabel>Unit</CFormLabel>
                                    <div class="form-control bg-light">
                                        {{ selectedProduct.unit || 'unit' }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <CFormLabel>Unit Price</CFormLabel>
                                <CInputGroup>
                                    <CInputGroupText>PKR</CInputGroupText>
                                    <CFormInput :value="getProductPrice(selectedProduct)" readonly class="bg-light" />
                                </CInputGroup>
                            </div>

                            <div class="mb-3">
                                <CFormLabel>Total</CFormLabel>
                                <div class="h5 text-primary">
                                    PKR {{ formatCurrency(editTotal) }}
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <CButton color="primary" @click="addOrUpdateProduct">
                                    <CIcon name="cil-check" class="me-2" />
                                    {{ isEditingExisting ? 'Update' : 'Add to List' }}
                                </CButton>
                            </div>
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
                            <CButton color="light" size="sm" @click="clearAllProducts">
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
                            <p class="text-muted">Search and add products from the left panel</p>
                        </div>

                        <!-- Products Table -->
                        <div v-else class="products-table">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="30%">Product</th>
                                            <th width="15%" class="text-center">Stock</th>
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
                                                <small class="text-muted">{{ getProductSKU(item.product_id) }}</small>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge" :class="getStockBadgeClass(item)">
                                                    {{ getProductStock(item.product_id) }}
                                                </span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="mx-2">{{ item.quantity }}</span>
                                                    <small class="text-muted">{{ getProductUnit(item.product_id)
                                                        }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="text-muted">PKR</span>
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
                                                        <CIcon icon="cilTrash" size="lg" />

                                                    </CButton>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                            <td colspan="3" class="text-start fw-bold">
                                                PKR {{ formatCurrency(subtotal) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end">Discount:</td>
                                            <td colspan="3" class="text-start">
                                                <CInputGroup size="sm" class="w-auto d-inline-flex">
                                                    <CFormInput v-model.number="discount" type="number" min="0"
                                                        style="width: 100px;" />
                                                    <CInputGroupText>PKR</CInputGroupText>
                                                </CInputGroup>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end">Tax (13%):</td>
                                            <td colspan="3" class="text-start">
                                                PKR {{ formatCurrency(taxAmount) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold h5">Total Amount:</td>
                                            <td colspan="3" class="text-start fw-bold h5 text-primary">
                                                PKR {{ formatCurrency(totalAmount) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>

                <!-- Receive Payment Card (only for Sales) -->
<CCard v-if="form.stock_type === 'sale' && form.party_name !== ''" class="mt-4 shadow-sm">
  <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-semibold">
      <CIcon name="cil-money" class="me-2 text-success" />
      Receive Payment from Customer
    </h6>
  </CCardHeader>
  <CCardBody>
    <div class="row g-4">
      <!-- Previous Balance -->
      <div class="col-md-4">
        <div class="border rounded p-3 text-center bg-light">
          <small class="text-muted d-block mb-1">Previous Balance</small>
          <h5 :class="getBalanceClass(previousBalance)">
            PKR {{ formatCurrency(Math.abs(previousBalance)) }}
          </h5>
          <small class="d-block mt-1">
            {{ previousBalance > 0 ? 'Customer Owes' : previousBalance < 0 ? 'You Owe Customer' : 'Zero Balance' }}
          </small>
        </div>
      </div>

      <!-- Receive Payment Input -->
      <div class="col-md-4">
        <CFormLabel class="fw-semibold">
          Receive Payment Now
        </CFormLabel>
        <CInputGroup>
          <CInputGroupText>PKR</CInputGroupText>
          <CFormInput
            type="number"
            v-model.number="receivePayment.amount"
            min="0"
            step="0.01"
            placeholder="0.00"
          />
        </CInputGroup>
        <small class="text-muted d-block mt-1">
          Max: PKR {{ formatCurrency(totalAmount) }}
        </small>
      </div>

      <!-- Amount Due / New Balance -->
      <div class="col-md-4">
        <div class="border rounded p-3 text-center" :class="getAmountDueClass">
          <small class="text-muted d-block mb-1">Amount Due / New Balance</small>
          <h4 :class="getBalanceClass(amountDueAfterPayment)">
            PKR {{ formatCurrency(Math.abs(amountDueAfterPayment)) }}
          </h4>
          <small class="d-block mt-1">
            {{ amountDueAfterPayment > 0 ? 'Customer Still Owes' : amountDueAfterPayment < 0 ? 'Customer Has Credit' : 'Fully Paid' }}
          </small>
        </div>
      </div>
    </div>
  </CCardBody>
</CCard>

                <!-- Actions Card -->
                <CCard class="mt-4 shadow-sm">
                    <CCardBody>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <CFormCheck label="Save as draft" id="save-draft" v-model="saveAsDraftFlag" />
                                <small class="text-muted d-block mt-1">Save for later completion</small>
                            </div>
                            <div class="d-flex gap-3">
                                <CButton color="light" @click="printReceipt" :disabled="form.items.length === 0">
                                    <CIcon name="cil-print" class="me-2" />
                                    Print Preview
                                </CButton>
                                <CButton color="secondary" @click="resetForm">
                                    <CIcon name="cil-reload" class="me-2" />
                                    Reset
                                </CButton>
                                <CButton color="primary" @click="submitForm"
                                    :disabled="submitting || form.items.length === 0">
                                    <CSpinner v-if="submitting" component="span" size="sm" class="me-2" />
                                    <CIcon v-else name="cil-save" class="me-2" />
                                    {{ submitting ? 'Saving...' : 'Save Record' }}
                                </CButton>
                            </div>
                        </div>
                    </CCardBody>
                </CCard>
            </div>
        </div>

        <!-- Party Search Modal -->
        <CModal :visible="showPartySearch" @close="showPartySearch = false" size="lg">
            <CModalHeader>
                <CModalTitle>Select {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }}</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div class="mb-3">
                    <CInputGroup>
                        <CInputGroupText>
                            <CIcon name="cil-search" />
                        </CInputGroupText>
                        <CFormInput v-model="partySearchQuery" placeholder="Search..." />
                    </CInputGroup>
                </div>

                <div class="list-group">
                    <div v-for="party in filteredParties" :key="party.id"
                        class="list-group-item list-group-item-action cursor-pointer" @click="selectParty(party)">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ party.name }}</div>
                                <small class="text-muted">{{ party.phone || 'No phone' }}</small>
                            </div>
                            <CIcon name="cil-chevron-right" />
                        </div>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="showPartySearch = false">Cancel</CButton>
                <CButton color="primary" @click="createNewParty">Create New</CButton>
            </CModalFooter>
        </CModal>

        <!-- Create New Customer/Supplier Modal -->
<CModal :visible="showCreatePartyModal" @close="closeCreatePartyModal" size="lg">
  <CModalHeader>
    <CModalTitle>
      <CIcon name="cil-plus" class="me-2" />
      Create New {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }}
    </CModalTitle>
  </CModalHeader>
  <CModalBody>
    <!-- Show general error -->
    <div v-if="createPartyError" class="alert alert-danger">
      {{ createPartyError }}
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <CFormLabel>
          Name <span class="text-danger">*</span>
        </CFormLabel>
        <CFormInput v-model="createPartyForm.name" :invalid="createPartyErrors?.name" />
        <CFormFeedback v-if="createPartyErrors?.name" invalid>
          {{ createPartyErrors.name[0] }}
        </CFormFeedback>
      </div>

      <div class="col-md-6">
        <CFormLabel>Phone</CFormLabel>
        <CFormInput v-model="createPartyForm.phone" :invalid="createPartyErrors?.phone" />
        <CFormFeedback v-if="createPartyErrors?.phone" invalid>
          {{ createPartyErrors.phone[0] }}
        </CFormFeedback>
      </div>

      <div class="col-md-6">
        <CFormLabel>Email</CFormLabel>
        <CFormInput type="email" v-model="createPartyForm.email" :invalid="createPartyErrors?.email" />
        <CFormFeedback v-if="createPartyErrors?.email" invalid>
          {{ createPartyErrors.email[0] }}
        </CFormFeedback>
      </div>

      <div class="col-md-6">
        <CFormLabel>CNIC (Optional)</CFormLabel>
        <CFormInput v-model="createPartyForm.cnic" placeholder="xxxxx-xxxxxxx-x"
          :invalid="createPartyErrors?.cnic" />
        <CFormFeedback v-if="createPartyErrors?.cnic" invalid>
          {{ createPartyErrors.cnic[0] }}
        </CFormFeedback>
      </div>

      <div class="col-12">
        <CFormLabel>Address</CFormLabel>
        <CTextarea v-model="createPartyForm.address" rows="2" :invalid="createPartyErrors?.address" />
        <CFormFeedback v-if="createPartyErrors?.address" invalid>
          {{ createPartyErrors.address[0] }}
        </CFormFeedback>
      </div>

      <div class="col-12">
        <CFormLabel>Notes</CFormLabel>
        <CTextarea v-model="createPartyForm.notes" rows="2" placeholder="Additional notes..." />
      </div>
    </div>
  </CModalBody>
  <CModalFooter>
    <CButton color="secondary" @click="closeCreatePartyModal">Cancel</CButton>
    <CButton color="primary" @click="saveNewParty" :disabled="creatingParty">
      <CSpinner v-if="creatingParty" component="span" size="sm" class="me-2" />
      Create {{ form.stock_type === 'sale' ? 'Customer' : 'Supplier' }}
    </CButton>
  </CModalFooter>
</CModal>

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
                    <h5 class="mb-3">Stock Entry Created Successfully!</h5>
                    <p class="text-muted">{{ successMessage }}</p>

                    <div class="alert alert-light mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Reference</small>
                                <strong>{{ createdStock?.reference_no }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total Amount</small>
                                <strong class="text-primary">PKR {{ formatCurrency(createdStock?.net_price) }}</strong>
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
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const router = useRouter()

// State
const availableProducts = ref([])
const submitting = ref(false)
const showSuccessModal = ref(false)
const showPartySearch = ref(false)
const showAdvanced = ref(false)
const showAllProducts = ref(false)
const saveAsDraftFlag = ref(false)
const errors = ref({})

// Search & Selection
const searchQuery = ref('')
const searchResults = ref([])
const selectedProduct = ref(null)
const editQuantity = ref(1)
const editTotal = ref(0)
const isEditingExisting = ref(false)
const editingIndex = ref(-1)

// Party search
const partySearchQuery = ref('')
const parties = ref([])

// Form data
const form = reactive({
    stock_type: 'sale',
    date: new Date().toISOString().split('T')[0],
    description: '',
    party_name: '',
    party_phone: '',
    party_address: '',
    party_email: '',
    items: []
})

const discount = ref(0)
const successMessage = ref('')
const createdStock = ref(null)

// Create Party Modal State
const showCreatePartyModal = ref(false)
const createPartyForm = reactive({
  name: '',
  phone: '',
  email: '',
  cnic: '',
  address: '',
  notes: ''
})
const createPartyErrors = ref({})
const createPartyError = ref('')
const creatingParty = ref(false)

// Receive Payment
const receivePayment = reactive({
  amount: 0,
  method: 'cash',
  reference_no: '',
  notes: ''
})

// Computed: Amount due after receiving payment
const amountDueAfterPayment = computed(() => {
  const bill = totalAmount.value || 0
  const received = receivePayment.amount || 0
  const prevBalance = previousBalance.value || 0

  // For sale: previous balance (positive = customer owes)
  // After sale: customer owes prev + bill
  // After receiving payment: customer owes (prev + bill - received)
  return prevBalance + bill - received
})

// Class for amount due display
const getAmountDueClass = computed(() => {
  if (amountDueAfterPayment.value > 0) return 'bg-danger-subtle text-danger'
  if (amountDueAfterPayment.value < 0) return 'bg-success-subtle text-success'
  return 'bg-success-subtle text-success'
})


watch(editQuantity, (newVal) => {
    console.log('editQuantity changed:', newVal, typeof newVal)
})

watch(editTotal, (newVal) => {
    console.log('editTotal:', newVal)
})


// Computed properties
const filteredProducts = computed(() => {
    if (showAllProducts.value) {
        return availableProducts.value
    }
    return searchResults.value.length > 0 ? searchResults.value : availableProducts.value.slice(0, 10)
})

const selectedParty = computed(() => {
    if (parties.value.length > 0 && form.party_name) {
        return parties.value.find(
            party => party.name === form.party_name
        ) || null
    }
    return null
})

const previousBalance = computed(() => {
    if (selectedParty.value) {
        return selectedParty.value.current_balance || 0
    }
    return 0
})

const filteredParties = computed(() => {
    if (!partySearchQuery.value) return parties.value.slice(0, 10)
    return parties.value.filter(party =>
        party.name.toLowerCase().includes(partySearchQuery.value.toLowerCase()) ||
        (party.phone && party.phone.includes(partySearchQuery.value))
    )
})


const subtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const itemTotal = Number(item.total) || 0
        return sum + itemTotal
    }, 0)
})


const taxAmount = computed(() => {
    return (subtotal.value - discount.value) * 0.13
})

const totalAmount = computed(() => {
    const subtotalValue = subtotal.value - discount.value + taxAmount.value

    // For sales: customer owes (add to their balance)
    // For purchases: we owe supplier (add to their balance)
    if (form.stock_type === 'sale') {
        return subtotalValue
    }
    return subtotalValue
})

// Methods
const fetchProducts = async () => {
    try {
        const response = await axios.get('/active-products')
        console.log('products data', response.data)
        availableProducts.value = response.data
    } catch (error) {
        console.error('Error fetching products:', error)
    }
}


// Open create party modal
const createNewParty = () => {
  // Reset form and errors
  Object.assign(createPartyForm, {
    name: '',
    phone: '',
    email: '',
    cnic: '',
    address: '',
    notes: ''
  })
  createPartyErrors.value = {}
  createPartyError.value = ''
  showCreatePartyModal.value = true
  showPartySearch.value = false // Close search modal
}

// Close create party modal
const closeCreatePartyModal = () => {
  showCreatePartyModal.value = false
}

// Save new party
const saveNewParty = async () => {
  creatingParty.value = true
  createPartyErrors.value = {}
  createPartyError.value = ''

  try {
    const endpoint = form.stock_type === 'sale' ? '/customers' : '/suppliers'
    const response = await axios.post(endpoint, createPartyForm)

    if (response.data.success) {
      const newParty = response.data.data

      // Refresh parties list
      await fetchParties()

      // Automatically select the newly created party
      form.party_name = newParty.name
      handlePartyChange(newParty.name)

      Swal.fire({
        title: 'Success!',
        text: `${form.stock_type === 'sale' ? 'Customer' : 'Supplier'} created and selected successfully!`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Close modal
      closeCreatePartyModal()
    }
  } catch (error) {
    if (error.response?.status === 422) {
      // Validation errors
      createPartyErrors.value = error.response.data.errors || {}
      Swal.fire({
        title: 'Validation Error',
        html: Object.values(createPartyErrors.value)
          .map(err => err[0])
          .join('<br>'),
        icon: 'error'
      })
    } else {
      // General error
      createPartyError.value = error.response?.data?.message || 'Failed to create party'
      Swal.fire({
        title: 'Error',
        text: createPartyError.value,
        icon: 'error'
      })
    }
  } finally {
    creatingParty.value = false
  }
}

const handlePartyChange = (partyName) => {
    form.party_name = partyName

    // Find and populate party details
    if (partyName) {
        const party = parties.value.find(p => p.name === partyName)
        if (party) {
            form.party_phone = party.phone || ''
            form.party_address = party.address || ''
            form.party_email = party.email || ''
        }
    } else {
        // Clear party details if no party selected
        form.party_phone = ''
        form.party_address = ''
        form.party_email = ''
    }

    // Clear any existing errors
    clearError('party_name')
}

const getBalanceClass = (balance) => {
    if (balance > 0) return 'text-danger' // Owes money
    if (balance < 0) return 'text-success' // Has credit/we owe them
    return 'text-muted'
}

const fetchParties = async () => {
    try {
        // This would be your API endpoint for customers/suppliers
        const endpoint = form.stock_type === 'sale' ? '/customers' : '/suppliers'
        const response = await axios.get(endpoint)
        console.log('customers', response.data.data.data)
        parties.value = response.data.data.data || []
    } catch (error) {
        console.error('Error fetching parties:', error)
    }
}

const setTransactionType = (type) => {
    form.stock_type = type
    form.party_name = ''
    form.party_phone = ''
    form.party_address = ''
    form.party_email = ''
    form.items = []
    selectedProduct.value = null
    fetchParties()
}

const searchProducts = () => {
    if (!searchQuery.value.trim()) {
        showAllProducts.value = true
        return
    }

    const query = searchQuery.value.toLowerCase()
    searchResults.value = availableProducts.value.filter(product =>
        product.name.toLowerCase().includes(query) ||
        (product.sku && product.sku.toLowerCase().includes(query))
    )
    showAllProducts.value = false
}

const clearSearch = () => {
    selectedProduct.value = ''
    searchResults.value = []
    showAllProducts.value = false
}

const selectProductForEdit = (product) => {
    if (!product || !product.id) return

    selectedProduct.value = product

    // Force strict comparison (ID might be string vs number)
    const existingIndex = form.items.findIndex(
        item => Number(item.product_id) === Number(product.id)
    )

    if (existingIndex > -1) {
        // Product already in list → force EDIT mode
        const existingItem = form.items[existingIndex]
        editQuantity.value = existingItem.quantity || 1
        isEditingExisting.value = true
        editingIndex.value = existingIndex

        // Optional visual feedback
        console.log(`Editing existing product at index ${existingIndex}`)
    } else {
        // New product
        editQuantity.value = 1
        isEditingExisting.value = false
        editingIndex.value = -1
    }

    updateEditTotal()

    // Smooth scroll to edit form
    nextTick(() => {
        const editForm = document.querySelector('.product-edit-form')
        if (editForm) {
            editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
        }
    })
}

const clearSelectedProduct = () => {
    selectedProduct.value = null
    isEditingExisting.value = false
    editingIndex.value = -1
}

const updateEditTotal = () => {
    if (!selectedProduct.value) {
        editTotal.value = 0
        return
    }

    const qty = Number(editQuantity.value) || 1  // Default to 1 if invalid
    const price = getProductPrice(selectedProduct.value)

    editTotal.value = Math.round(qty * price * 100) / 100  // Avoid floating point issues
}
// Watch and sanitize immediately
watch(editQuantity, (newVal) => {
    let sanitized = Number(newVal) || 0
    if (sanitized < 1) sanitized = 0  // Prevent 0 or negative
    if (newVal !== sanitized) {
        editQuantity.value = sanitized
    }
    updateEditTotal()
}, { immediate: true })

const getProductPrice = (product) => {
    if (!product) return 0

    const priceField = form.stock_type === 'sale' ? 'sale_price' : 'purchase_price'
    const rawPrice = product[priceField]

    const parsed = parseFloat(rawPrice)
    return isNaN(parsed) ? 0 : parsed
}

const addOrUpdateProduct = () => {
    if (!selectedProduct.value) return

    const qty = Number(editQuantity.value)
    if (isNaN(qty) || qty < 1) {
        Swal.fire({
            title: 'Invalid Quantity',
            text: 'Please enter a valid quantity',
            icon: 'warning'
        })
        return
    }

    const price = getProductPrice(selectedProduct.value)
    const total = Math.round(qty * price * 100) / 100

    const productItem = {
        product_id: selectedProduct.value.id,
        quantity: qty,
        total: total
    }

    // Double-check if this product is already in the list (in case flags got reset)
    const currentIndexInList = form.items.findIndex(
        item => Number(item.product_id) === Number(selectedProduct.value.id)
    )

    if (currentIndexInList > -1) {
        // Always update if exists — even if flags are wrong
        form.items[currentIndexInList] = productItem
        console.log('Updated existing product (safety fallback)')
    } else if (isEditingExisting.value && editingIndex.value > -1) {
        // Normal update path
        form.items[editingIndex.value] = productItem
    } else {
        // Only add new if truly not in list
        form.items.push(productItem)
    }

    // Always reset after action
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
        updateEditTotal()

        // Scroll to edit form
        nextTick(() => {
            document.querySelector('.product-edit-form')?.scrollIntoView({ behavior: 'smooth' })
        })
    }
}

const removeProduct = (index) => {
    form.items.splice(index, 1)
}

const clearAllProducts = () => {
    if (confirm('Are you sure you want to remove all products?')) {
        form.items = []
    }
}

const selectParty = (party) => {
    form.party_name = party.name
    form.party_phone = party.phone || ''
    form.party_address = party.address || ''
    form.party_email = party.email || ''
    showPartySearch.value = false

    // Clear search query
    partySearchQuery.value = ''
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

const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

// Form submission
const submitForm = async () => {
  submitting.value = true
  errors.value = {}

  if (form.items.length === 0) {
    Swal.fire({
      title: 'No Products Added',
      text: 'Please add at least one product',
      icon: 'warning'
    })
    submitting.value = false
    return
  }

  // Prepare data
  const submitData = {
    ...form,
    discount: discount.value,
    tax_amount: taxAmount.value,
    net_price: totalAmount.value,
    is_draft: saveAsDraftFlag.value,
    items: form.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_price: getUnitPrice(item.product_id),
      total_price: item.total
    }))
  }

  // Add receive payment if sale and amount > 0
  if (form.stock_type === 'sale' && receivePayment.amount > 0) {
    submitData.receive_payment = {
      amount: receivePayment.amount,
      payment_method: receivePayment.method,
      reference_no: receivePayment.reference_no,
      notes: receivePayment.notes
    }
  }

  try {
    const response = await axios.post('/stocks', submitData)
console.log(response.data)
    if (response.data.success) {
      createdStock.value = response.data.data
      successMessage.value = `${form.stock_type === 'sale' ? 'Sale' : 'Purchase'} record saved successfully!`
      showSuccessModal.value = true
    }
  } catch (error) {
    console.log(error)
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      Swal.fire({
        title: 'Validation Error',
        html: Object.values(errors.value)
          .flat()
          .join('<br>'),
        icon: 'error'
      })
    } else {
      Swal.fire({
        title: 'Error',
        text: error.response?.data?.message || 'Failed to create stock',
        icon: 'error'
      })
    }
  } finally {
    submitting.value = false
  }
}

const resetForm = async () => {
    const result = await Swal.fire({
        title: 'Reset Form?',
        text: 'All data will be lost. Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, reset it',
        cancelButtonText: 'Cancel'
    })
    if (result.isConfirmed) {
        form.stock_type = 'purchase'
        form.date = new Date().toISOString().split('T')[0]
        form.description = ''
        form.party_name = ''
        form.party_phone = ''
        form.party_address = ''
        form.party_email = ''
        form.items = []
        discount.value = 0
        selectedProduct.value = null
        searchQuery.value = ''
        errors.value = {}
        saveAsDraftFlag.value = false

        Swal.fire({
            title: 'Form Reset',
            text: 'All data has been cleared.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        })
    }
}


const printReceipt = () => {
    // Pre-format all values
    const formattedDate = new Date(form.date).toLocaleDateString('en-PK')
    const formattedTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    const formattedSubtotal = formatCurrency(subtotal.value)
    const formattedDiscount = formatCurrency(discount.value)
    const formattedTax = formatCurrency(taxAmount.value)
    const formattedTotal = formatCurrency(totalAmount.value)

    // Generate items HTML - SINGLE LINE PER ROW
    const itemsHtml = form.items.map((item, index) => {
        const product = availableProducts.value.find(p => p.id == item.product_id)
        const productName = product ? product.name : 'Unknown Product'
        const unitPrice = getUnitPrice(item.product_id)
        const total = item.total || (item.quantity * unitPrice)

        // Don't display unit, trim name
        let displayName = productName.trim()
        if (displayName.length > 18) {
            displayName = displayName.substring(0, 15) + '...'
        }

        // Single line HTML with NO extra whitespace
        return `<tr><td class="col-desc">${displayName}</td><td class="col-qty">${item.quantity}</td><td class="col-price">${formatCurrency(unitPrice)}</td><td class="col-total">${formatCurrency(total)}</td></tr>`
    }).join('')

    const printWindow = window.open('', '_blank')
    if (!printWindow) {
        Swal.fire({
            title: 'Popup Blocked',
            text: 'Please allow popups for printing',
            icon: 'warning'
        })
        return
    }

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

                .center { text-align: center; }

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

                .store-phone { font-size: 10px; }
                .receipt-info { margin: 8px 0; font-size: 10px; }
                .receipt-info div { margin: 2px 0; }

                /* PERFECTLY ALIGNED TABLE */
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 8px 0;
                    font-size: 10px;
                    table-layout: fixed; /* CRITICAL */
                }

                /* FIXED COLUMN WIDTHS (total 80mm = 226px approx) */
                .items-table th.col-qty,
                .items-table td.col-qty {
                    width: 40px;        /* 15% */
                    text-align: left;
                    padding-left: 0;
                }

                .items-table th.col-desc,
                .items-table td.col-desc {
                    width: 106px;       /* 47% */
                    text-align: left;
                    padding: 0 2px;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }

                .items-table th.col-price,
                .items-table td.col-price {
                    width: 40px;        /* 18% */
                    text-align: right;
                    padding-right: 5px;
                }

                .items-table th.col-total,
                .items-table td.col-total {
                    width: 40px;        /* 18% */
                    text-align: right;
                    padding-right: 0;
                }

                .items-table th {
                    border-bottom: 2px solid #000;
                    padding: 4px 0;
                    font-weight: bold;
                }

                .items-table td {
                    padding: 3px 0;
                    vertical-align: top;
                    border-bottom: 1px solid #eee;
                }

                /* Total section */
                .total-section {
                    border-top: 2px solid #000;
                    margin-top: 10px;
                    padding-top: 5px;
                }

                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 2px 0;
                }

                .grand-total {
                    font-size: 12px;
                    font-weight: bold;
                    border-top: 2px solid #000;
                    margin-top: 5px;
                    padding-top: 5px;
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
                    ${form.stock_type.toUpperCase()} RECEIPT
                </div>

                <!-- Receipt Details -->
                <div class="receipt-info">
                    <div>Date: ${formattedDate}</div>
                    <div>Time: ${formattedTime}</div>
                    ${form.party_name ? `
                    <div style="margin-top: 5px;">
                        <div><strong>${form.stock_type === 'sale' ? 'Customer' : 'Supplier'}:</strong></div>
                        <div>${form.party_name}</div>
                        ${form.party_phone ? `<div>Phone: ${form.party_phone}</div>` : ''}
                    </div>
                    ` : ''}
                </div>

                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">

                <!-- Items Table -->
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
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <hr style="border: none; border-top: 1px dashed #000; margin: 8px 0;">

                <!-- Totals Section -->
                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>PKR ${formattedSubtotal}</span>
                    </div>
                    ${discount.value > 0 ? `<div class="total-row"><span>Discount:</span><span>PKR ${formattedDiscount}</span></div>` : ''}
                    ${taxAmount.value > 0 ? `<div class="total-row"><span>Tax (13%):</span><span>PKR ${formattedTax}</span></div>` : ''}
                    <div class="total-row grand-total">
                        <span>NET TOTAL:</span>
                        <span>PKR ${formattedTotal}</span>
                    </div>
                </div>

${selectedParty.value && form.stock_type === 'sale' ? `
<div style="margin-top: 5px;">
    <div><strong>Previous Balance:</strong> PKR ${formatCurrency(previousBalance)}</div>
    <div><strong>New Balance:</strong> PKR ${formatCurrency(previousBalance + totalAmount.value)}</div>
</div>
` : ''}

                <!-- Footer -->
                <div class="footer">
                    <div>Printed: ${new Date().toLocaleDateString()} ${formattedTime}</div>
                    <div style="margin-top: 5px;">Thank you for your business!</div>
                    <div style="font-size: 8px; margin-top: 3px;">*Goods once sold are not returnable*</div>
                    <div style="font-size: 8px; margin-top: 3px; color: #999;">[This is a preview - Not a valid receipt]</div>
                </div>
            </div>
        </body>
        </html>
    `

    printWindow.document.write(printContent)
    printWindow.document.close()

    // Auto-print
    setTimeout(() => {
        if (!printWindow.closed) {
            printWindow.focus()
            printWindow.print()
            printWindow.close()
        }
    }, 200)
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
    }
}

// Lifecycle
onMounted(() => {
    fetchProducts()
    fetchParties()
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

.product-search-results {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
}

.product-list .list-group-item {
    border-left: none;
    border-right: none;
    border-radius: 0;
    transition: all 0.2s ease;
}

.product-list .list-group-item:first-child {
    border-top: none;
}

.product-list .list-group-item:last-child {
    border-bottom: none;
}

.product-list .list-group-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.product-edit-form {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
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

/* Scrollbar styling */
.product-search-results::-webkit-scrollbar,
.products-table::-webkit-scrollbar {
    width: 6px;
}

.product-search-results::-webkit-scrollbar-track,
.products-table::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.product-search-results::-webkit-scrollbar-thumb,
.products-table::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.product-search-results::-webkit-scrollbar-thumb:hover,
.products-table::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

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
}
</style>
