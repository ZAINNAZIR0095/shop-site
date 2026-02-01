<template>
    <div class="customers-wrapper">
        <!-- Header -->
        <div class="header-section mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold text-dark">
                    <CIcon name="cil-user" class="me-2" />
                    Customers Management
                </h1>
                <div class="d-flex align-items-center gap-2">
                    <CButton color="primary" @click="openCreateModal">
                        <CIcon name="cil-plus" class="me-2" />
                        Add New Customer
                    </CButton>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <CCard class="mb-4 shadow-sm">
            <CCardBody>
                <div class="row g-3">
                    <div class="col-md-6">
                        <CInputGroup>
                            <CInputGroupText>
                                <CIcon name="cil-search" />
                            </CInputGroupText>
                            <CFormInput v-model="filters.search" placeholder="Search by name, phone, or CNIC..."
                                @keyup.enter="fetchCustomers" />
                            <CButton color="light" @click="fetchCustomers">
                                Search
                            </CButton>
                            <CButton color="light" @click="clearFilters">
                                <CIcon name="cil-reload" />
                            </CButton>
                        </CInputGroup>
                    </div>
                    <div class="col-md-3">
                        <CFormSelect v-model="filters.status" @change="fetchCustomers">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </CFormSelect>
                    </div>
                    <div class="col-md-3">
                        <CFormSelect v-model="filters.balance" @change="fetchCustomers">
                            <option value="all">All Balances</option>
                            <option value="positive">Positive Balance</option>
                            <option value="negative">Negative Balance</option>
                            <option value="zero">Zero Balance</option>
                        </CFormSelect>
                    </div>
                </div>
            </CCardBody>
        </CCard>

        <!-- Customers Table -->
        <CCard class="shadow-sm">
            <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    Customers List
                    <span class="badge bg-primary ms-2">{{ customers.total }}</span>
                </h6>
                <small class="text-muted">
                    Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }}
                </small>
            </CCardHeader>
            <CCardBody class="p-0">
                <!--  Loading State -->
                <div v-if="loading" class="d-flex justify-content-center align-items-center py-5">
                    <CSpinner color="primary" />
                    <span class="ms-3 text-muted">Loading customers...</span>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Current Balance</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(customer, index) in customers.data" :key="customer.id">
                                <td class="align-middle">{{ customers.from + index }}</td>
                                <td class="align-middle">
                                    <div class="fw-semibold">{{ customer.name }}</div>
                                    <small v-if="customer.cnic" class="text-muted">CNIC: {{ customer.cnic }}</small>
                                </td>
                                <td class="align-middle">
                                    <div v-if="customer.phone">
                                        <CIcon name="cil-phone" class="me-1 text-muted" />
                                        {{ customer.phone }}
                                    </div>
                                    <div v-if="customer.email">
                                        <CIcon name="cil-envelope-closed" class="me-1 text-muted" />
                                        <small>{{ customer.email }}</small>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <CBadge :color="customer.is_active ? 'success' : 'secondary'">
                                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                                    </CBadge>
                                </td>
                                <td class="text-end align-middle">
                                    <span :class="getBalanceClass(customer.current_balance)" class="fw-bold">
                                        PKR {{ formatCurrency(Math.abs(customer.current_balance)) }}
                                    </span>
                                    <div v-if="customer.current_balance !== 0" class="text-muted small">
                                        {{ customer.current_balance > 0 ? 'Owes' : 'To Receive' }}
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark">
                                        {{ customer.transactions_count }}
                                    </span>
                                </td>
                                <td class="text-start align-middle">
                                    <div class="btn-group btn-group-sm">
                                        <CButton color="light" size="sm" @click="viewDetails(customer)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path
                                                    d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                <path
                                                    d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                            </svg> <!-- OR Method 2: Using name prop -->
                                        </CButton>
                                        <CButton color="light" size="sm" @click="editCustomer(customer)">
                                            <CIcon name="cil-pencil" />
                                        </CButton>
                                        <CButton v-if="customer.current_balance != 0" color="light" size="sm"
                                            @click="openPaymentModal(customer)">
                                            <CIcon name="cil-money" />
                                        </CButton>
                                        <!-- <CButton color="light" size="sm" @click="confirmDelete(customer)">
                                            <CIcon name="cil-trash" />
                                        </CButton> -->
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <CIcon name="cil-user" size="3xl" class="mb-3" />
                                        <h5>No customers found</h5>
                                        <p>Try changing your filters or add a new customer</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CCardBody>
            <CCardFooter v-if="customers.total > 0" class="bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Page {{ customers.current_page }} of {{ customers.last_page }}
                    </small>
                    <div>
                        <CButtonGroup>
                            <CButton color="light" size="sm" :disabled="!customers.prev_page_url" @click="prevPage">
                                Previous
                            </CButton>
                            <CButton color="light" size="sm" :disabled="!customers.next_page_url" @click="nextPage">
                                Next
                            </CButton>
                        </CButtonGroup>
                    </div>
                </div>
            </CCardFooter>
        </CCard>

        <!-- Create/Edit Customer Modal -->
        <CModal :visible="showCustomerModal" @close="closeCustomerModal" size="lg">
            <CModalHeader>
                <CModalTitle>{{ isEditing ? 'Edit Customer' : 'Add New Customer' }}</CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div v-if="formErrors" class="alert alert-danger">
                    <ul class="mb-0">
                        <li v-for="(error, field) in formErrors" :key="field">
                            {{ error[0] }}
                        </li>
                    </ul>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <CFormLabel>
                            Full Name <span class="text-danger">*</span>
                        </CFormLabel>
                        <CFormInput v-model="customerForm.name" :invalid="formErrors?.name" />
                        <CFormFeedback v-if="formErrors?.name" invalid>
                            {{ formErrors.name[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Phone Number</CFormLabel>
                        <CFormInput v-model="customerForm.phone" :invalid="formErrors?.phone" />
                        <CFormFeedback v-if="formErrors?.phone" invalid>
                            {{ formErrors.phone[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Email Address</CFormLabel>
                        <CFormInput type="email" v-model="customerForm.email" :invalid="formErrors?.email" />
                        <CFormFeedback v-if="formErrors?.email" invalid>
                            {{ formErrors.email[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>CNIC</CFormLabel>
                        <CFormInput v-model="customerForm.cnic" placeholder="xxxxx-xxxxxxx-x"
                            :invalid="formErrors?.cnic" />
                        <CFormFeedback v-if="formErrors?.cnic" invalid>
                            {{ formErrors.cnic[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Address</CFormLabel>
                        <CTextarea v-model="customerForm.address" rows="2" :invalid="formErrors?.address" />
                        <CFormFeedback v-if="formErrors?.address" invalid>
                            {{ formErrors.address[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Opening Balance</CFormLabel>
                        <CInputGroup>
                            <CInputGroupText>PKR</CInputGroupText>
                            <CFormInput type="number" v-model.number="customerForm.opening_balance" min="0"
                                step="0.01" />
                        </CInputGroup>
                        <small class="text-muted">Initial balance when adding customer</small>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Status</CFormLabel>
                        <div class="mt-2">
                            <CFormCheck inline label="Active" :checked="customerForm.is_active"
                                @change="customerForm.is_active = true" />
                            <CFormCheck inline label="Inactive" :checked="!customerForm.is_active"
                                @change="customerForm.is_active = false" />
                        </div>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Notes</CFormLabel>
                        <CTextarea v-model="customerForm.notes" rows="3" placeholder="Additional notes..." />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeCustomerModal">Cancel</CButton>
                <CButton color="primary" @click="saveCustomer" :disabled="saving">
                    <CSpinner v-if="saving" component="span" size="sm" class="me-2" />
                    {{ isEditing ? 'Update' : 'Save' }} Customer
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Customer Details Modal -->
        <CModal :visible="showDetailsModal" @close="closeDetailsModal" size="xl">
            <CModalHeader>
                <CModalTitle>
                    <CIcon name="cil-user" class="me-2" />
                    Customer Details
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div v-if="selectedCustomer">
                    <!-- Customer Info -->
                    <CCard class="mb-4">
                        <CCardBody>
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="mb-3">{{ selectedCustomer.name }}</h4>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Phone</small>
                                            <div>
                                                <CIcon name="cil-phone" class="me-1" />
                                                {{ selectedCustomer.phone || 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Email</small>
                                            <div>
                                                <CIcon name="cil-envelope-closed" class="me-1" />
                                                {{ selectedCustomer.email || 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">CNIC</small>
                                            {{ selectedCustomer.cnic || 'N/A' }}
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Status</small>
                                            <CBadge :color="selectedCustomer.is_active ? 'success' : 'secondary'">
                                                {{ selectedCustomer.is_active ? 'Active' : 'Inactive' }}
                                            </CBadge>
                                        </div>
                                    </div>
                                    <div v-if="selectedCustomer.address">
                                        <small class="text-muted d-block">Address</small>
                                        <p class="mb-0">{{ selectedCustomer.address }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="balance-card p-3 border rounded text-center">
                                        <small class="text-muted d-block">Current Balance</small>
                                        <h2 :class="getBalanceClass(selectedCustomer.current_balance)">
                                            PKR {{ formatCurrency(Math.abs(selectedCustomer.current_balance)) }}
                                        </h2>
                                        <div class="text-muted small">
                                            {{ selectedCustomer.current_balance > 0 ? 'Customer Owes' : 'To Receive from Customer' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CCardBody>
                    </CCard>

                    <!-- Balance Summary -->
                    <CCard class="mb-4">
                        <CCardHeader>
                            <h6 class="mb-0">Balance Summary</h6>
                        </CCardHeader>
                        <CCardBody>
                            <div class="row text-center">
                                <div class="col">
                                    <small class="text-muted d-block">Opening Balance</small>
                                    <h5 class="mb-0">PKR {{ formatCurrency(selectedCustomer.opening_balance || 0) }}
                                    </h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Total Sales</small>
                                    <h5 class="mb-0 text-danger">PKR {{ formatCurrency(balanceSummary.total_debit || 0)
                                    }}</h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Total Payments</small>
                                    <h5 class="mb-0 text-success">PKR {{ formatCurrency(balanceSummary.total_credit ||
                                        0) }}</h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Current Balance</small>
                                    <h5 :class="getBalanceClass(selectedCustomer.current_balance)" class="mb-0">
                                        PKR {{ formatCurrency(Math.abs(selectedCustomer.current_balance)) }}
                                    </h5>
                                </div>
                            </div>
                        </CCardBody>
                    </CCard>

                    <!-- Transactions Tabs -->
                    <CTabs>
                        <CTabList variant="tabs">
                            <CTab @click="fetchCustomerTransactions">All Transactions</CTab>
                        </CTabList>
                        <CTabPanel>
                            <div v-if="selectedCustomer.transactions && selectedCustomer.transactions.length > 0">
                                <!-- Client-side pagination controls -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <small class="text-muted">
                                        Showing {{ transactionsFrom }} to {{ transactionsTo }} of {{
                                            selectedCustomer.transactions.length }} transactions
                                    </small>

                                    <!-- Pagination -->
                                    <CPagination v-if="totalTransactionPages > 1" align="end" size="sm">
                                        <CPaginationItem :disabled="currentTransactionPage === 1"
                                            @click="currentTransactionPage--">
                                            Previous
                                        </CPaginationItem>

                                        <CPaginationItem v-for="page in totalTransactionPages" :key="page"
                                            :active="page === currentTransactionPage"
                                            @click="currentTransactionPage = page">
                                            {{ page }}
                                        </CPaginationItem>

                                        <CPaginationItem :disabled="currentTransactionPage === totalTransactionPages"
                                            @click="currentTransactionPage++">
                                            Next
                                        </CPaginationItem>
                                    </CPagination>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                                <th class="text-end">Balance</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="transaction in paginatedTransactions" :key="transaction.id">
                                                <td>{{ formatDate(transaction.date) }}</td>
                                                <td>
                                                    <CBadge :color="getTransactionTypeColor(transaction.type)">
                                                        {{ transaction.type }}
                                                    </CBadge>
                                                </td>
                                                <td>{{ transaction.description }}</td>
                                                <td class="text-end text-danger">
                                                    <span v-if="transaction.debit > 0">
                                                        PKR {{ formatCurrency(transaction.debit) }}
                                                    </span>
                                                </td>
                                                <td class="text-end text-success">
                                                    <span v-if="transaction.credit > 0">
                                                        PKR {{ formatCurrency(transaction.credit) }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    PKR {{ formatCurrency(transaction.balance) }}
                                                </td>
                                                <td class="text-end">
                                                    <CButton v-if="transaction.type === 'payment'" size="sm"
                                                        color="warning"
                                                        @click="openEditCustomerPaymentModal(transaction)">
                                                        Edit
                                                    </CButton>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div v-else class="text-center py-5 text-muted">
                                <CIcon name="cil-info-circle" size="3xl" class="mb-3" />
                                <h6>No transactions found</h6>
                            </div>
                        </CTabPanel>
                    </CTabs>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeDetailsModal">Close</CButton>
                <CButton color="primary" @click="editCustomer(selectedCustomer)">
                    Edit Customer
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Edit Payment Modal (for Customer) -->
        <CModal :visible="editCustomerPaymentModal" @close="editCustomerPaymentModal = false">
            <CModalHeader>
                <CModalTitle>Edit Customer Payment</CModalTitle>
            </CModalHeader>

            <CModalBody>
                <div class="row g-3">
                    <div class="col-md-6">
                        <CFormLabel>Date</CFormLabel>
                        <CFormInput type="date" v-model="editCustomerPaymentForm.date" />
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Amount (PKR)</CFormLabel>
                        <CInputGroup>
                            <CInputGroupText>PKR</CInputGroupText>
                            <CFormInput type="number" v-model.number="editCustomerPaymentForm.amount" min="1"
                                step="0.01" />
                        </CInputGroup>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Payment Method</CFormLabel>
                        <CFormSelect v-model="editCustomerPaymentForm.payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </CFormSelect>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Reference Number</CFormLabel>
                        <CFormInput v-model="editCustomerPaymentForm.reference_no" />
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Notes</CFormLabel>
                        <CFormInput v-model="editCustomerPaymentForm.notes" />
                    </div>
                </div>
            </CModalBody>

            <CModalFooter>
                <CButton color="secondary" @click="editCustomerPaymentModal = false">
                    Cancel
                </CButton>
                <CButton color="primary" @click="updateCustomerPayment" :disabled="processingPayment">
                    <CSpinner v-if="processingPayment" component="span" size="sm" class="me-2" />
                    Update Payment
                </CButton>
            </CModalFooter>
        </CModal>


        <!-- Payment Modal -->
        <CModal :visible="showPaymentModal" @close="closePaymentModal">
            <CModalHeader>
                <CModalTitle>
                    <CIcon name="cil-money" class="me-2" />
                    Record Payment
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div v-if="selectedCustomer">
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading">{{ selectedCustomer.name }}</h6>
                        Current Balance:
                        <span :class="getBalanceClass(selectedCustomer.current_balance)" class="fw-bold">
                            PKR {{ formatCurrency(Math.abs(selectedCustomer.current_balance)) }}
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <CFormLabel>
                                Date <span class="text-danger">*</span>
                            </CFormLabel>
                            <CFormInput type="date" v-model="paymentForm.date" />
                        </div>
                        <div class="col-md-6">
                            <CFormLabel>
                                Amount <span class="text-danger">*</span>
                            </CFormLabel>
                            <CInputGroup>
                                <CInputGroupText>PKR</CInputGroupText>
                                <CFormInput type="number" v-model.number="paymentForm.amount"
                                    :max="Math.abs(selectedCustomer.current_balance)" min="1" step="0.01" />
                            </CInputGroup>
                            <small class="text-muted">
                                Maximum: PKR {{ formatCurrency(Math.abs(selectedCustomer.current_balance)) }}
                            </small>
                        </div>
                        <div class="col-12">
                            <CFormLabel>
                                Payment Method <span class="text-danger">*</span>
                            </CFormLabel>
                            <CFormSelect v-model="paymentForm.payment_method">
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </CFormSelect>
                        </div>
                        <div class="col-md-6">
                            <CFormLabel>Reference Number</CFormLabel>
                            <CFormInput v-model="paymentForm.reference_no"
                                placeholder="Check #, Transaction ID, etc." />
                        </div>
                        <div class="col-md-6">
                            <CFormLabel>Notes</CFormLabel>
                            <CFormInput v-model="paymentForm.notes" placeholder="Optional notes..." />
                        </div>
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closePaymentModal">Cancel</CButton>
                <CButton color="primary" @click="savePayment" :disabled="processingPayment">
                    <CSpinner v-if="processingPayment" component="span" size="sm" class="me-2" />
                    Record Payment
                </CButton>
            </CModalFooter>
        </CModal>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import { cibEyeem } from '@coreui/icons'
import * as icons from '@coreui/icons'


// State
const customers = ref({
    data: [],
    total: 0,
    per_page: 20,
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0
})

const loading = ref(false);

const filters = reactive({
    search: '',
    status: 'active',
    balance: 'all'
})

const showCustomerModal = ref(false)
const showDetailsModal = ref(false)
const showPaymentModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const processingPayment = ref(false)
const formErrors = ref(null)
const selectedCustomer = ref(null)
const balanceSummary = ref({})
const customerTransactions = ref({})
const transactionsLoading = ref(false)

// Forms
const customerForm = reactive({
    id: null,
    name: '',
    phone: '',
    email: '',
    address: '',
    cnic: '',
    notes: '',
    opening_balance: 0,
    is_active: true
})

const paymentForm = reactive({
    date: new Date().toISOString().split('T')[0],
    amount: 0,
    payment_method: 'cash',
    reference_no: '',
    notes: ''
})

// Add these reactive variables for client-side pagination
const currentTransactionPage = ref(1)
const transactionsPerPage = ref(10)

// Add these new refs
const editCustomerPaymentModal = ref(false)
const editCustomerPaymentForm = reactive({
    id: null,           // This will be the PAYMENT ID (not transaction id)
    date: '',
    amount: 0,
    payment_method: '',
    reference_no: '',
    notes: ''
})

// Computed properties for pagination
const totalTransactionPages = computed(() => {
    if (!selectedCustomer.value?.transactions) return 1
    return Math.ceil(selectedCustomer.value.transactions.length / transactionsPerPage.value)
})

const transactionsFrom = computed(() => {
    return (currentTransactionPage.value - 1) * transactionsPerPage.value + 1
})

const transactionsTo = computed(() => {
    const end = currentTransactionPage.value * transactionsPerPage.value
    return Math.min(end, selectedCustomer.value?.transactions?.length || 0)
})

const paginatedTransactions = computed(() => {
    if (!selectedCustomer.value?.transactions) return []
    const start = (currentTransactionPage.value - 1) * transactionsPerPage.value
    const end = start + transactionsPerPage.value
    return selectedCustomer.value.transactions.slice(start, end)
})

// Optional: Reset page when modal opens or customer changes
watch(showDetailsModal, (newVal) => {
    if (newVal) {
        currentTransactionPage.value = 1 // Reset to first page when opening details
    }
})

// Methods
const fetchCustomers = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page: page,
            per_page: customers.value.per_page,
            ...filters
        }

        const response = await axios.get('/customers', { params });
        customers.value = response.data.data
        loading.value = false;
    } catch (error) {
        loading.value = false;
        console.error('Error fetching customers:', error)
        Swal.fire({
            title: 'Error',
            text: 'Failed to load customers',
            icon: 'error'
        })
    }
}


// Open edit modal for customer payment
const openEditCustomerPaymentModal = (transaction) => {
    if (!transaction?.payment_id) {
        Swal.fire({
            title: 'Error',
            text: 'This payment cannot be edited (missing payment ID)',
            icon: 'error'
        })
        return
    }

    editCustomerPaymentForm.id = transaction.payment_id
    editCustomerPaymentForm.date = transaction.date ? transaction.date.split('T')[0] : ''
    editCustomerPaymentForm.amount = transaction.credit || 0   // Payment = credit for customer
    editCustomerPaymentForm.payment_method = transaction.payment_method || 'cash'
    editCustomerPaymentForm.reference_no = transaction.reference_no || ''
    editCustomerPaymentForm.notes = transaction.notes || ''

    editCustomerPaymentModal.value = true;
    closeDetailsModal();
}

// Update customer payment
const updateCustomerPayment = async () => {
    if (!editCustomerPaymentForm.id) return
    const seletedCustomer = selectedCustomer.value
    processingPayment.value = true

    try {
        await axios.put(`/customers/payments/${editCustomerPaymentForm.id}`, editCustomerPaymentForm)

        Swal.fire({
            title: 'Updated!',
            text: 'Payment updated successfully',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        editCustomerPaymentModal.value = false

        // Refresh data
        console.log(selectedCustomer.value)
        // Only refresh details if modal is still open and customer is selected
        await viewDetails(seletedCustomer)

        await fetchCustomers()

    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update payment',
            icon: 'error'
        })
    } finally {
        processingPayment.value = false
    }
}

const clearFilters = () => {
    filters.search = ''
    filters.status = 'active'
    filters.balance = 'all'
    fetchCustomers(1)
}

const prevPage = () => {
    if (customers.value.current_page > 1) {
        fetchCustomers(customers.value.current_page - 1)
    }
}

const nextPage = () => {
    if (customers.value.current_page < customers.value.last_page) {
        fetchCustomers(customers.value.current_page + 1)
    }
}

const openCreateModal = () => {
    resetCustomerForm()
    isEditing.value = false
    formErrors.value = null
    showCustomerModal.value = true
}

const editCustomer = (customer) => {
    Object.assign(customerForm, {
        id: customer.id,
        name: customer.name,
        phone: customer.phone || '',
        email: customer.email || '',
        address: customer.address || '',
        cnic: customer.cnic || '',
        notes: customer.notes || '',
        opening_balance: customer.opening_balance || 0,
        is_active: customer.is_active
    })
    isEditing.value = true
    formErrors.value = null
    showCustomerModal.value = true
    showDetailsModal.value = false
}

const closeCustomerModal = () => {
    showCustomerModal.value = false
    resetCustomerForm()
}

const resetCustomerForm = () => {
    Object.assign(customerForm, {
        id: null,
        name: '',
        phone: '',
        email: '',
        address: '',
        cnic: '',
        notes: '',
        opening_balance: 0,
        is_active: true
    })
}

const saveCustomer = async () => {
    saving.value = true
    formErrors.value = null

    try {
        const url = customerForm.id ? `/customers/${customerForm.id}` : '/customers'
        const method = customerForm.id ? 'put' : 'post'

        const response = await axios[method](url, customerForm)

        Swal.fire({
            title: 'Success!',
            text: response.data.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        closeCustomerModal()
        fetchCustomers()

    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Failed to save customer',
                icon: 'error'
            })
        }
    } finally {
        saving.value = false
    }
}

const viewDetails = async (customer) => {
    try {
        const response = await axios.get(`/customers/${customer.id}`)
        selectedCustomer.value = response.data.data.customer
        balanceSummary.value = response.data.data.balance_summary
        showDetailsModal.value = true
    } catch (error) {
        console.log(error)
        Swal.fire({
            title: 'Error',
            text: 'Failed to load customer details',
            icon: 'error'
        })
    }
}

const closeDetailsModal = () => {
    showDetailsModal.value = false
    // selectedCustomer.value = null
    balanceSummary.value = {}
    customerTransactions.value = {}
}

const fetchCustomerTransactions = async (page = 1) => {
    if (!selectedCustomer.value) return

    transactionsLoading.value = true
    try {
        const response = await axios.get(`/customers/${selectedCustomer.value.id}/transactions`, {
            params: { page, per_page: 10 }
        })
        customerTransactions.value = response.data.data
    } catch (error) {
        console.error('Error fetching transactions:', error)
    } finally {
        transactionsLoading.value = false
    }
}

const changeTransactionPage = (page) => {
    fetchCustomerTransactions(page)
}

const openPaymentModal = (customer) => {
    selectedCustomer.value = customer
    paymentForm.amount = Math.min(Math.abs(customer.current_balance), 10000)
    showPaymentModal.value = true
}

const closePaymentModal = () => {
    showPaymentModal.value = false
    resetPaymentForm()
}

const resetPaymentForm = () => {
    Object.assign(paymentForm, {
        date: new Date().toISOString().split('T')[0],
        amount: 0,
        payment_method: 'cash',
        reference_no: '',
        notes: ''
    })
}

const savePayment = async () => {
    if (!selectedCustomer.value) return

    processingPayment.value = true

    try {
        const response = await axios.post(`/customers/${selectedCustomer.value.id}/payments`, paymentForm)

        Swal.fire({
            title: 'Success!',
            text: response.data.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        closePaymentModal()
        fetchCustomers()
        if (showDetailsModal.value) {
            viewDetails(selectedCustomer.value)
        }

    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors
            Swal.fire({
                title: 'Validation Error',
                html: Object.values(errors).map(err => err[0]).join('<br>'),
                icon: 'error'
            })
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Failed to record payment',
                icon: 'error'
            })
        }
    } finally {
        processingPayment.value = false
    }
}

const confirmDelete = (customer) => {
    Swal.fire({
        title: 'Delete Customer?',
        text: `Are you sure you want to delete ${customer.name}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/customers/${customer.id}`)

                Swal.fire({
                    title: 'Deleted!',
                    text: 'Customer has been deleted.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                })

                fetchCustomers()

            } catch (error) {
                const message = error.response?.data?.message || 'Failed to delete customer'
                Swal.fire({
                    title: 'Error',
                    text: message,
                    icon: 'error'
                })
            }
        }
    })
}

// Helper Methods
const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getBalanceClass = (balance) => {
    if (balance > 0) return 'text-danger' // Customer owes money
    if (balance < 0) return 'text-success' // Customer has credit
    return 'text-muted'
}

const getTransactionTypeColor = (type) => {
    const colors = {
        sale: 'danger',
        payment: 'success',
        opening_balance: 'info',
        adjustment: 'warning'
    }
    return colors[type] || 'secondary'
}

// Lifecycle
onMounted(() => {
    fetchCustomers()
})
</script>

<style scoped>
.customers-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.header-section {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 15px;
}

.balance-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6 !important;
}

.table td,
.table th {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #b3d7ff;
}

/* Transaction table styling */
.table-sm td,
.table-sm th {
    padding: 0.5rem;
}

/* Modal customization */
.modal-xl {
    max-width: 1200px;
}
</style>
