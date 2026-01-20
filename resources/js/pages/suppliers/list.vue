<template>
    <div class="suppliers-wrapper">
        <!-- Header -->
        <div class="header-section mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 fw-bold text-dark">
                    <CIcon name="cil-factory" class="me-2" />
                    Suppliers Management
                </h1>
                <div class="d-flex align-items-center gap-2">
                    <CButton color="primary" @click="openCreateModal">
                        <CIcon name="cil-plus" class="me-2" />
                        Add New Supplier
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
                                @keyup.enter="fetchSuppliers" />
                            <CButton color="light" @click="fetchSuppliers">
                                Search
                            </CButton>
                            <CButton color="light" @click="clearFilters">
                                <CIcon name="cil-reload" />
                            </CButton>
                        </CInputGroup>
                    </div>
                    <div class="col-md-3">
                        <CFormSelect v-model="filters.status" @change="fetchSuppliers">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </CFormSelect>
                    </div>
                    <div class="col-md-3">
                        <CFormSelect v-model="filters.balance" @change="fetchSuppliers">
                            <option value="all">All Balances</option>
                            <option value="positive">We Owe (Positive)</option>
                            <option value="negative">They Owe (Negative)</option>
                            <option value="zero">Zero Balance</option>
                        </CFormSelect>
                    </div>
                </div>
            </CCardBody>
        </CCard>

        <!-- Suppliers Table -->
        <CCard class="shadow-sm">
            <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    Suppliers List
                    <span class="badge bg-primary ms-2">{{ suppliers.total }}</span>
                </h6>
                <small class="text-muted">
                    Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }}
                </small>
            </CCardHeader>
            <CCardBody class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Supplier</th>
                                <th>Contact</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Current Balance</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(supplier, index) in suppliers.data" :key="supplier.id">
                                <td class="align-middle">{{ suppliers.from + index }}</td>
                                <td class="align-middle">
                                    <div class="fw-semibold">{{ supplier.name }}</div>
                                    <small v-if="supplier.cnic" class="text-muted">CNIC: {{ supplier.cnic }}</small>
                                </td>
                                <td class="align-middle">
                                    <div v-if="supplier.phone">
                                        <CIcon name="cil-phone" class="me-1 text-muted" />
                                        {{ supplier.phone }}
                                    </div>
                                    <div v-if="supplier.email">
                                        <CIcon name="cil-envelope-closed" class="me-1 text-muted" />
                                        <small>{{ supplier.email }}</small>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <CBadge :color="supplier.is_active ? 'success' : 'secondary'">
                                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                    </CBadge>
                                </td>
                                <td class="text-end align-middle">
                                    <span :class="getBalanceClass(supplier.current_balance)" class="fw-bold">
                                        PKR {{ formatCurrency(Math.abs(supplier.current_balance)) }}
                                    </span>
                                    <div v-if="supplier.current_balance !== 0" class="text-muted small">
                                        {{ getBalanceText(supplier.current_balance) }}
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-light text-dark">
                                        {{ supplier.transactions_count }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group btn-group-sm">
                                        <CButton color="light" size="sm" @click="viewDetails(supplier)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path
                                                    d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                                <path
                                                    d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                            </svg>
                                        </CButton>
                                        <CButton color="light" size="sm" @click="editSupplier(supplier)">
                                            <CIcon name="cil-pencil" />
                                        </CButton>
                                        <CButton v-if="supplier.current_balance != 0" color="light" size="sm"
                                            @click="openPaymentModal(supplier)">
                                            <CIcon name="cil-money" />
                                        </CButton>
                                        <CButton color="light" size="sm" @click="confirmDelete(supplier)">
                                            <CIcon name="cil-trash" />
                                        </CButton>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="suppliers.data.length === 0">
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <CIcon name="cil-factory" size="3xl" class="mb-3" />
                                        <h5>No suppliers found</h5>
                                        <p>Try changing your filters or add a new supplier</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CCardBody>
            <CCardFooter v-if="suppliers.total > 0" class="bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Page {{ suppliers.current_page }} of {{ suppliers.last_page }}
                    </small>
                    <div>
                        <CButtonGroup>
                            <CButton color="light" size="sm" :disabled="!suppliers.prev_page_url" @click="prevPage">
                                Previous
                            </CButton>
                            <CButton color="light" size="sm" :disabled="!suppliers.next_page_url" @click="nextPage">
                                Next
                            </CButton>
                        </CButtonGroup>
                    </div>
                </div>
            </CCardFooter>
        </CCard>

        <!-- Create/Edit Supplier Modal -->
        <CModal :visible="showSupplierModal" @close="closeSupplierModal" size="lg">
            <CModalHeader>
                <CModalTitle>{{ isEditing ? 'Edit Supplier' : 'Add New Supplier' }}</CModalTitle>
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
                            Supplier Name <span class="text-danger">*</span>
                        </CFormLabel>
                        <CFormInput v-model="supplierForm.name" :invalid="formErrors?.name" />
                        <CFormFeedback v-if="formErrors?.name" invalid>
                            {{ formErrors.name[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Phone Number</CFormLabel>
                        <CFormInput v-model="supplierForm.phone" :invalid="formErrors?.phone" />
                        <CFormFeedback v-if="formErrors?.phone" invalid>
                            {{ formErrors.phone[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Email Address</CFormLabel>
                        <CFormInput type="email" v-model="supplierForm.email" :invalid="formErrors?.email" />
                        <CFormFeedback v-if="formErrors?.email" invalid>
                            {{ formErrors.email[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>CNIC</CFormLabel>
                        <CFormInput v-model="supplierForm.cnic" placeholder="xxxxx-xxxxxxx-x"
                            :invalid="formErrors?.cnic" />
                        <CFormFeedback v-if="formErrors?.cnic" invalid>
                            {{ formErrors.cnic[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Address</CFormLabel>
                        <CTextarea v-model="supplierForm.address" rows="2" :invalid="formErrors?.address" />
                        <CFormFeedback v-if="formErrors?.address" invalid>
                            {{ formErrors.address[0] }}
                        </CFormFeedback>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Opening Balance</CFormLabel>
                        <CInputGroup>
                            <CInputGroupText>PKR</CInputGroupText>
                            <CFormInput type="number" v-model.number="supplierForm.opening_balance" step="0.01" />
                        </CInputGroup>
                        <small class="text-muted">
                            Positive: Supplier initially owes you (credit)<br>
                            Negative: You initially owe supplier (debit)
                        </small>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Status</CFormLabel>
                        <div class="mt-2">
                            <CFormCheck inline label="Active" :checked="supplierForm.is_active"
                                @change="supplierForm.is_active = true" />
                            <CFormCheck inline label="Inactive" :checked="!supplierForm.is_active"
                                @change="supplierForm.is_active = false" />
                        </div>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Notes</CFormLabel>
                        <CTextarea v-model="supplierForm.notes" rows="3" placeholder="Additional notes..." />
                    </div>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeSupplierModal">Cancel</CButton>
                <CButton color="primary" @click="saveSupplier" :disabled="saving">
                    <CSpinner v-if="saving" component="span" size="sm" class="me-2" />
                    {{ isEditing ? 'Update' : 'Save' }} Supplier
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Supplier Details Modal -->
        <CModal :visible="showDetailsModal" @close="closeDetailsModal" size="xl">
            <CModalHeader>
                <CModalTitle>
                    <CIcon name="cil-factory" class="me-2" />
                    Supplier Details
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div v-if="selectedSupplier">
                    <!-- Supplier Info -->
                    <CCard class="mb-4">
                        <CCardBody>
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="mb-3">{{ selectedSupplier.name }}</h4>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Phone</small>
                                            <div>
                                                <CIcon name="cil-phone" class="me-1" />
                                                {{ selectedSupplier.phone || 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Email</small>
                                            <div>
                                                <CIcon name="cil-envelope-closed" class="me-1" />
                                                {{ selectedSupplier.email || 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">CNIC</small>
                                            {{ selectedSupplier.cnic || 'N/A' }}
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Status</small>
                                            <CBadge :color="selectedSupplier.is_active ? 'success' : 'secondary'">
                                                {{ selectedSupplier.is_active ? 'Active' : 'Inactive' }}
                                            </CBadge>
                                        </div>
                                    </div>
                                    <div v-if="selectedSupplier.address">
                                        <small class="text-muted d-block">Address</small>
                                        <p class="mb-0">{{ selectedSupplier.address }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="balance-card p-3 border rounded text-center">
                                        <small class="text-muted d-block">Current Balance</small>
                                        <h2 :class="getBalanceClass(selectedSupplier.current_balance)">
                                            PKR {{ formatCurrency(Math.abs(selectedSupplier.current_balance)) }}
                                        </h2>
                                        <div class="text-muted small">
                                            {{ getBalanceText(selectedSupplier.current_balance) }}
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
                                    <h5 class="mb-0">PKR {{ formatCurrency(selectedSupplier.opening_balance || 0) }}
                                    </h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Total Purchases</small>
                                    <h5 class="mb-0 text-danger">PKR {{ formatCurrency(balanceSummary.total_credit || 0)
                                    }}</h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Total Payments</small>
                                    <h5 class="mb-0 text-success">PKR {{ formatCurrency(balanceSummary.total_debit ||
                                        0) }}</h5>
                                </div>
                                <div class="col">
                                    <small class="text-muted d-block">Current Balance</small>
                                    <h5 :class="getBalanceClass(selectedSupplier.current_balance)" class="mb-0">
                                        PKR {{ formatCurrency(Math.abs(selectedSupplier.current_balance)) }}
                                    </h5>
                                </div>
                            </div>
                        </CCardBody>
                    </CCard>

                    <!-- Transactions Tabs -->
                    <CTabs>
                        <CTabList variant="tabs">
                            <CTab>All Transactions</CTab>
                        </CTabList>

                        <CTabPanel>
                            <!-- Loading state -->
                            <div v-if="transactionsLoading" class="text-center py-5">
                                <CSpinner color="primary" />
                                <p class="mt-3">Loading transactions...</p>
                            </div>

                            <!-- No transactions -->
                            <div v-else-if="!transactionsWithRunningBalance.length" class="text-center py-5 text-muted">
                                <CIcon name="cil-folder-open" size="3xl" class="mb-3" />
                                <h5>No transactions found</h5>
                            </div>

                            <!-- Main paginated content with manual running balance -->
                            <div v-else>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Reference</th>
                                                <th>Description</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                                <th class="text-end">Running Balance</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="transaction in transactionsWithRunningBalance"
                                                :key="transaction.id">
                                                <td>{{ formatDate(transaction.date) }}</td>
                                                <td>
                                                    <CBadge :color="getTransactionTypeColor(transaction.type)">
                                                        {{ transaction.type }}
                                                    </CBadge>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ transaction.reference_no || 'N/A'
                                                        }}</small>
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
                                                    <span :class="getBalanceClass(transaction.displayRunningBalance)">
                                                        PKR {{
                                                        formatCurrency(Math.abs(transaction.displayRunningBalance)) }}
                                                    </span>
                                                    <small class="d-block text-muted mt-1">
                                                        {{ getBalanceText(transaction.displayRunningBalance) }}
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <CButton v-if="transaction.type === 'payment'" size="sm"
                                                        color="warning" @click="openEditPaymentModal(transaction)">
                                                        Edit
                                                    </CButton>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination remains the same -->
                                <div class="d-flex justify-content-center mt-4">
                                    <CPagination v-if="supplierTransactions.last_page > 1" align="center">
                                        <CPaginationItem v-for="page in supplierTransactions.last_page" :key="page"
                                            :active="page === supplierTransactions.current_page"
                                            @click="changeTransactionPage(page)">
                                            {{ page }}
                                        </CPaginationItem>
                                    </CPagination>
                                </div>
                            </div>
                        </CTabPanel>
                    </CTabs>
                </div>
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="closeDetailsModal">Close</CButton>
                <CButton color="primary" @click="editSupplier(selectedSupplier)">
                    Edit Supplier
                </CButton>
            </CModalFooter>
        </CModal>

        <!-- Payment Modal -->
        <CModal :visible="showPaymentModal" @close="closePaymentModal">
            <CModalHeader>
                <CModalTitle>
                    <CIcon name="cil-money" class="me-2" />
                    Record Payment to Supplier
                </CModalTitle>
            </CModalHeader>
            <CModalBody>
                <div v-if="selectedSupplier">
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading">{{ selectedSupplier.name }}</h6>
                        Current Balance:
                        <span :class="getBalanceClass(selectedSupplier.current_balance)" class="fw-bold">
                            PKR {{ formatCurrency(Math.abs(selectedSupplier.current_balance)) }}
                        </span>
                        <div class="mt-1 small">
                            {{ getBalanceText(selectedSupplier.current_balance) }}
                        </div>
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
                                Amount (PKR) <span class="text-danger">*</span>
                            </CFormLabel>
                            <CInputGroup>
                                <CInputGroupText>PKR</CInputGroupText>
                                <CFormInput type="number" v-model.number="paymentForm.amount" min="1" step="0.01" />
                            </CInputGroup>
                            <small class="text-muted">
                                Maximum: PKR {{ formatCurrency(Math.abs(selectedSupplier.current_balance)) }}
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

                        <!-- Balance Preview -->
                        <div class="col-12">
                            <CCard class="bg-light">
                                <CCardBody>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Current Balance</small>
                                            <h5 :class="getBalanceClass(selectedSupplier.current_balance)">
                                                PKR {{ formatCurrency(Math.abs(selectedSupplier.current_balance)) }}
                                            </h5>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Balance After Payment</small>
                                            <h5 :class="getBalanceClass(newBalanceAfterPayment)">
                                                PKR {{ formatCurrency(Math.abs(newBalanceAfterPayment)) }}
                                            </h5>
                                        </div>
                                    </div>
                                </CCardBody>
                            </CCard>
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

        <!-- Edit Payment Modal -->
        <CModal :visible="editPaymentModal" @close="editPaymentModal = false">
            <CModalHeader>
                <CModalTitle>Edit Payment</CModalTitle>
            </CModalHeader>

            <CModalBody>
                <div class="row g-3">
                    <div class="col-md-6">
                        <CFormLabel>Date</CFormLabel>
                        <CFormInput type="date" v-model="editPaymentForm.date" />
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Amount</CFormLabel>
                        <CInputGroup>
                            <CInputGroupText>PKR</CInputGroupText>
                            <CFormInput type="number" v-model.number="editPaymentForm.amount" min="1" />
                        </CInputGroup>
                    </div>

                    <div class="col-12">
                        <CFormLabel>Payment Method</CFormLabel>
                        <CFormSelect v-model="editPaymentForm.payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </CFormSelect>
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Reference</CFormLabel>
                        <CFormInput v-model="editPaymentForm.reference_no" />
                    </div>

                    <div class="col-md-6">
                        <CFormLabel>Notes</CFormLabel>
                        <CFormInput v-model="editPaymentForm.notes" />
                    </div>
                </div>
            </CModalBody>

            <CModalFooter>
                <CButton color="secondary" @click="editPaymentModal = false">
                    Cancel
                </CButton>
                <CButton color="primary" @click="updatePayment">
                    Update Payment
                </CButton>
            </CModalFooter>
        </CModal>

    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

// State
const suppliers = ref({
    data: [],
    total: 0,
    per_page: 20,
    current_page: 1,
    last_page: 1,
    from: 0,
    to: 0
})

const loading = ref(false)

const filters = reactive({
    search: '',
    status: 'active',
    balance: 'all'
})

const showSupplierModal = ref(false)
const showDetailsModal = ref(false)
const showPaymentModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const processingPayment = ref(false)
const formErrors = ref(null)
const selectedSupplier = ref(null)
const balanceSummary = ref({})
const supplierTransactions = ref({})
const transactionsLoading = ref(false)
const editPaymentModal = ref(false)

const editPaymentForm = reactive({
    id: null,
    date: '',
    amount: 0,
    payment_method: '',
    reference_no: '',
    notes: ''
})


// Forms
const supplierForm = reactive({
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

// Computed
const newBalanceAfterPayment = computed(() => {
    if (!selectedSupplier.value) return 0
    const current = parseFloat(selectedSupplier.value.current_balance || 0)
    const payment = parseFloat(paymentForm.amount || 0)
    // Payment reduces what we owe supplier
    return current - payment
})

// Methods
const fetchSuppliers = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page: page,
            per_page: suppliers.value.per_page,
            ...filters
        }

        const response = await axios.get('/suppliers', { params });
        suppliers.value = response.data.data
        loading.value = false;
    } catch (error) {
        loading.value = false;
        console.error('Error fetching suppliers:', error)
        Swal.fire({
            title: 'Error',
            text: 'Failed to load suppliers',
            icon: 'error'
        })
    }
}

const transactionsWithRunningBalance = computed(() => {
    if (!supplierTransactions.value?.data?.length) return []

    // Start from the FINAL (current) balance
    let running = parseFloat(selectedSupplier.value?.current_balance || 0)

    // Copy and reverse: newest → oldest
    const reversed = [...supplierTransactions.value.data].reverse()

    const result = reversed.map(tx => {
        const debit = parseFloat(tx.debit || 0)
        const credit = parseFloat(tx.credit || 0)

        // Undo the transaction effect to get previous balance
        // Balance formula: opening + credit (purchases) - debit (payments)
        if (tx.type === 'payment') {
            // Payment decreased balance (debit) → to go back: we ADD debit
            running += debit
        }
        else if (['purchase', 'return'].includes(tx.type)) {
            // Purchase increased balance (credit) → to go back: we SUBTRACT credit
            running -= credit
        }
        else if (tx.type === 'opening_balance') {
            // Opening balance is initial credit → to go back: subtract it
            running -= credit
        }
        // Add other types if needed (adjustment...)

        return {
            ...tx,
            displayRunningBalance: running
        }
    })

    // Flip back to newest first (same order as table)
    return result.reverse()
})

function openEditPaymentModal(transaction) {
    // Safety check
    if (!transaction) {
        console.error('No transaction passed to edit modal');
        return;
    }

    console.log('Transaction data:', transaction); // Debug what we actually have

    // Set form fields - IMPORTANT: Use payment_id for the API call, not transaction.id
    editPaymentForm.id = transaction.payment_id || null;
    editPaymentForm.date = transaction.date
        ? transaction.date.split('T')[0]  // "2026-01-15T..." → "2026-01-15"
        : new Date().toISOString().split('T')[0];
    editPaymentForm.amount = transaction.debit || transaction.amount || 0;
    editPaymentForm.payment_method = transaction.payment_method || 'cash';
    editPaymentForm.reference_no = transaction.reference_no || '';
    editPaymentForm.notes = transaction.notes || '';

    // CRITICAL: Set selectedSupplier from transaction (assuming it has supplier info)
    // Option 1: If transaction has supplier object
    //   if (transaction.supplier_id) {
    //     selectedSupplier.value = transaction.supplier_id;
    //   }
    console.log(transaction.supplier_id, selectedSupplier.value)
    editPaymentModal.value = true;
}

const updatePayment = async () => {
    try {
        console.log('hello world', selectedSupplier.value, editPaymentForm)
        await axios.post(
            `/suppliers/${editPaymentForm.id}/payments/update`,
            editPaymentForm
        )

        Swal.fire({
            title: 'Updated!',
            text: 'Payment updated successfully',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        editPaymentModal.value = false

        // VERY IMPORTANT: reload fresh data
        await viewDetails(selectedSupplier.value)
        await fetchSuppliers()

    } catch (error) {
        console.log(error)
        Swal.fire({
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update payment',
            icon: 'error'
        })
    }
}


const clearFilters = () => {
    filters.search = ''
    filters.status = 'active'
    filters.balance = 'all'
    fetchSuppliers(1)
}

const prevPage = () => {
    if (suppliers.value.current_page > 1) {
        fetchSuppliers(suppliers.value.current_page - 1)
    }
}

const nextPage = () => {
    if (suppliers.value.current_page < suppliers.value.last_page) {
        fetchSuppliers(suppliers.value.current_page + 1)
    }
}

const openCreateModal = () => {
    resetSupplierForm()
    isEditing.value = false
    formErrors.value = null
    showSupplierModal.value = true
}

const editSupplier = (supplier) => {
    Object.assign(supplierForm, {
        id: supplier.id,
        name: supplier.name,
        phone: supplier.phone || '',
        email: supplier.email || '',
        address: supplier.address || '',
        cnic: supplier.cnic || '',
        notes: supplier.notes || '',
        opening_balance: supplier.opening_balance || 0,
        is_active: supplier.is_active
    })
    isEditing.value = true
    formErrors.value = null
    showSupplierModal.value = true
    showDetailsModal.value = false
}

const closeSupplierModal = () => {
    showSupplierModal.value = false
    resetSupplierForm()
}

const resetSupplierForm = () => {
    Object.assign(supplierForm, {
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

const saveSupplier = async () => {
    saving.value = true
    formErrors.value = null

    try {
        const url = supplierForm.id ? `/suppliers/${supplierForm.id}` : '/suppliers'
        const method = supplierForm.id ? 'put' : 'post'

        const response = await axios[method](url, supplierForm)

        Swal.fire({
            title: 'Success!',
            text: response.data.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        closeSupplierModal()
        fetchSuppliers()

    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Failed to save supplier',
                icon: 'error'
            })
        }
    } finally {
        saving.value = false
    }
}

const viewDetails = async (supplier) => {
    try {
        const response = await axios.get(`/suppliers/${supplier.id}`)
        selectedSupplier.value = response.data.data.supplier
        balanceSummary.value = response.data.data.balance_summary
        showDetailsModal.value = true

        // ── Load paginated transactions right away
        transactionsLoading.value = true
        await fetchSupplierTransactions(1)
    } catch (error) {
        Swal.fire({
            title: 'Error',
            text: 'Failed to load supplier details',
            icon: 'error'
        })
    }
}

const closeDetailsModal = () => {
    showDetailsModal.value = false
    if (!editPaymentModal) {
        selectedSupplier.value = null
    }
    balanceSummary.value = {}
    supplierTransactions.value = {}
}

const fetchSupplierTransactions = async (page = 1) => {
    if (!selectedSupplier.value) return

    transactionsLoading.value = true
    try {
        const response = await axios.get(`/suppliers/${selectedSupplier.value.id}/transactions`, {
            params: { page, per_page: 10 }
        })
        supplierTransactions.value = response.data.data
    } catch (error) {
        console.error('Error fetching transactions:', error)
    } finally {
        transactionsLoading.value = false
    }
}

const changeTransactionPage = (page) => {
    fetchSupplierTransactions(page)
}

const openPaymentModal = (supplier) => {
    selectedSupplier.value = supplier
    paymentForm.amount = Math.min(Math.abs(supplier.current_balance), 10000)
    showPaymentModal.value = true
    paymentForm.date = new Date().toISOString().split('T')[0];
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
    if (!selectedSupplier.value) return

    processingPayment.value = true

    try {
        const response = await axios.post(`/suppliers/${selectedSupplier.value.id}/payments`, paymentForm)

        Swal.fire({
            title: 'Success!',
            text: response.data.message,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        })

        closePaymentModal()
        fetchSuppliers()
        if (showDetailsModal.value) {
            viewDetails(selectedSupplier.value)
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
            console.log(error)
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

const confirmDelete = (supplier) => {
    Swal.fire({
        title: 'Delete Supplier?',
        text: `Are you sure you want to delete ${supplier.name}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/suppliers/${supplier.id}`)

                Swal.fire({
                    title: 'Deleted!',
                    text: 'Supplier has been deleted.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                })

                fetchSuppliers()

            } catch (error) {
                const message = error.response?.data?.message || 'Failed to delete supplier'
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
    const amount = parseFloat(balance)
    if (amount > 0) return 'text-danger' // We owe supplier (positive balance)
    if (amount < 0) return 'text-success' // Supplier owes us (negative balance)
    return 'text-muted'
}

const getBalanceText = (balance) => {
    const amount = parseFloat(balance)
    if (amount > 0) return 'We owe supplier'
    if (amount < 0) return 'Supplier owes us'
    return 'Zero balance'
}

const getTransactionTypeColor = (type) => {
    const colors = {
        purchase: 'danger', // Purchase increases what we owe (credit for supplier)
        payment: 'success', // Payment reduces what we owe (debit for supplier)
        opening_balance: 'info',
        adjustment: 'warning',
        return: 'warning'
    }
    return colors[type] || 'secondary'
}

// Lifecycle
onMounted(() => {
    fetchSuppliers()
})
</script>

<style scoped>
.suppliers-wrapper {
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

/* Different icon color for suppliers */
.text-supplier {
    color: #6f42c1;
}
</style>
