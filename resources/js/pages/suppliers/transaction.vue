<template>
  <div class="transactions-wrapper">
    <!-- Header -->
    <div class="header-section mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h1 class="h2 fw-bold text-dark">Supplier Transactions</h1>
          <p class="text-muted mb-0">
            <span v-if="supplier">{{ supplier.name }} • </span>
            Transaction History
          </p>
        </div>
        <div class="d-flex align-items-center gap-3">
          <CButton color="light" @click="goBack">
            <CIcon name="cil-arrow-left" class="me-2" />
            Back
          </CButton>
          <CButton color="primary" @click="showPaymentModal">
            <CIcon name="cil-cash" class="me-2" />
            Record Payment
          </CButton>
        </div>
      </div>
    </div>

    <!-- Supplier Info Card -->
    <CCard class="mb-4 shadow-sm" v-if="supplier">
      <CCardBody>
        <div class="row">
          <div class="col-md-8">
            <div class="d-flex align-items-center">
              <div class="avatar me-3">
                <div class="avatar-circle bg-primary text-white">
                  {{ supplier.name.charAt(0) }}
                </div>
              </div>
              <div>
                <h5 class="mb-1">{{ supplier.name }}</h5>
                <div class="text-muted small">
                  <span class="me-3">
                    <CIcon name="cil-phone" class="me-1" />
                    {{ supplier.phone || 'No phone' }}
                  </span>
                  <span v-if="supplier.email">
                    <CIcon name="cil-envelope-open" class="me-1" />
                    {{ supplier.email }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="balance-summary">
              <div class="text-end">
                <small class="text-muted d-block">Current Balance</small>
                <h3 :class="getBalanceClass(supplier.current_balance)">
                  PKR {{ formatCurrency(Math.abs(supplier.current_balance)) }}
                </h3>
                <small>{{ getBalanceText(supplier.current_balance) }}</small>
              </div>
            </div>
          </div>
        </div>
      </CCardBody>
    </CCard>

    <!-- Filters & Summary -->
    <div class="row g-4 mb-4">
      <div class="col-md-8">
        <CCard>
          <CCardBody>
            <div class="row g-3 align-items-end">
              <div class="col-md-4">
                <CFormLabel>Date Range</CFormLabel>
                <CFormInput type="date" v-model="filters.start_date" />
              </div>
              <div class="col-md-4">
                <CFormLabel>To</CFormLabel>
                <CFormInput type="date" v-model="filters.end_date" />
              </div>
              <div class="col-md-4">
                <CFormLabel>Type</CFormLabel>
                <CFormSelect v-model="filters.type">
                  <option value="all">All Types</option>
                  <option value="purchase">Purchases</option>
                  <option value="payment">Payments</option>
                  <option value="opening_balance">Opening Balance</option>
                </CFormSelect>
              </div>
            </div>
          </CCardBody>
        </CCard>
      </div>

      <div class="col-md-4">
        <CCard>
          <CCardBody class="text-center">
            <h6 class="text-muted mb-2">Running Balance</h6>
            <h4 :class="getBalanceClass(balanceSummary.running_balance)">
              PKR {{ formatCurrency(Math.abs(balanceSummary.running_balance)) }}
            </h4>
          </CCardBody>
        </CCard>
      </div>
    </div>

    <!-- Transactions Table -->
    <CCard class="shadow-sm">
      <CCardHeader class="bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Transaction History</h6>
        <span class="text-muted">
          {{ transactions.total || 0 }} transactions found
        </span>
      </CCardHeader>
      <CCardBody class="p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th width="10%">Date</th>
                <th width="15%">Type</th>
                <th width="20%">Description</th>
                <th width="15%">Reference</th>
                <th width="15%" class="text-end">Debit</th>
                <th width="15%" class="text-end">Credit</th>
                <th width="10%" class="text-end">Balance</th>
              </tr>
            </thead>
            <tbody>
              <!-- Opening Balance Row -->
              <tr class="table-info">
                <td colspan="4" class="text-end fw-bold">Opening Balance:</td>
                <td colspan="3" class="text-start fw-bold">
                  PKR {{ formatCurrency(balanceSummary.opening_balance) }}
                </td>
              </tr>

              <tr v-for="transaction in transactions.data" :key="transaction.id"
                  :class="getTransactionRowClass(transaction.type)">
                <td>{{ formatDate(transaction.date) }}</td>
                <td>
                  <span class="badge" :class="getTypeBadgeClass(transaction.type)">
                    {{ formatType(transaction.type) }}
                  </span>
                </td>
                <td>
                  <div>{{ transaction.description }}</div>
                  <small v-if="transaction.stock" class="text-muted">
                    Invoice: {{ transaction.stock.reference_no }}
                  </small>
                </td>
                <td>
                  <small class="text-muted">{{ transaction.reference_no || 'N/A' }}</small>
                </td>
                <td class="text-end">
                  <span v-if="transaction.debit > 0" class="text-danger fw-bold">
                    PKR {{ formatCurrency(transaction.debit) }}
                  </span>
                </td>
                <td class="text-end">
                  <span v-if="transaction.credit > 0" class="text-success fw-bold">
                    PKR {{ formatCurrency(transaction.credit) }}
                  </span>
                </td>
                <td class="text-end fw-bold">
                  <span :class="getBalanceClass(transaction.running_balance)">
                    PKR {{ formatCurrency(Math.abs(transaction.running_balance)) }}
                  </span>
                  <br>
                  <small class="text-muted">
                    {{ getBalanceText(transaction.running_balance) }}
                  </small>
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="4" class="text-end fw-bold">Totals:</td>
                <td class="text-end fw-bold text-danger">
                  PKR {{ formatCurrency(balanceSummary.total_debit) }}
                </td>
                <td class="text-end fw-bold text-success">
                  PKR {{ formatCurrency(balanceSummary.total_credit) }}
                </td>
                <td class="text-end fw-bold">
                  <span :class="getBalanceClass(balanceSummary.current_balance)">
                    PKR {{ formatCurrency(Math.abs(balanceSummary.current_balance)) }}
                  </span>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </CCardBody>
      <CCardFooter v-if="transactions.data && transactions.data.length > 0" class="bg-light">
        <CPagination :pages="transactions.last_page" :active-page="transactions.current_page"
                    @update:active-page="changePage" align="end" />
      </CCardFooter>
    </CCard>

    <!-- Payment Modal -->
    <CModal :visible="showPaymentModal" @close="closePaymentModal" size="lg">
      <CModalHeader>
        <CModalTitle>
          <CIcon name="cil-cash" class="me-2" />
          Record Payment to Supplier
        </CModalTitle>
      </CModalHeader>
      <CModalBody>
        <div v-if="paymentErrors" class="alert alert-danger">
          <ul class="mb-0">
            <li v-for="(error, field) in paymentErrors" :key="field">
              {{ error[0] }}
            </li>
          </ul>
        </div>

        <CForm @submit.prevent="submitPayment">
          <div class="row g-3">
            <div class="col-md-6">
              <CFormLabel>Date <span class="text-danger">*</span></CFormLabel>
              <CFormInput v-model="paymentForm.date" type="date" required />
            </div>

            <div class="col-md-6">
              <CFormLabel>Amount (PKR) <span class="text-danger">*</span></CFormLabel>
              <CFormInput v-model="paymentForm.amount" type="number" step="0.01" min="0.01" required />
            </div>

            <div class="col-md-6">
              <CFormLabel>Payment Method <span class="text-danger">*</span></CFormLabel>
              <CFormSelect v-model="paymentForm.payment_method" required>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="other">Other</option>
              </CFormSelect>
            </div>

            <div class="col-md-6">
              <CFormLabel>Reference No</CFormLabel>
              <CFormInput v-model="paymentForm.reference_no" placeholder="Optional reference number" />
            </div>

            <div class="col-12">
              <CFormLabel>Notes</CFormLabel>
              <CFormTextarea v-model="paymentForm.notes" rows="3" placeholder="Additional notes..." />
            </div>

            <!-- Summary -->
            <div class="col-12">
              <CCard class="bg-light">
                <CCardBody>
                  <div class="row">
                    <div class="col-md-6">
                      <small class="text-muted d-block">Current Balance</small>
                      <h5 :class="getBalanceClass(supplier?.current_balance)">
                        PKR {{ formatCurrency(Math.abs(supplier?.current_balance || 0)) }}
                      </h5>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted d-block">New Balance After Payment</small>
                      <h5 :class="getBalanceClass(newBalanceAfterPayment)">
                        PKR {{ formatCurrency(Math.abs(newBalanceAfterPayment)) }}
                      </h5>
                    </div>
                  </div>
                </CCardBody>
              </CCard>
            </div>
          </div>
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closePaymentModal">Cancel</CButton>
        <CButton color="primary" @click="submitPayment" :disabled="processingPayment">
          <CSpinner v-if="processingPayment" component="span" size="sm" class="me-2" />
          {{ processingPayment ? 'Processing...' : 'Record Payment' }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const supplierId = route.params.id

// State
const supplier = ref(null)
const transactions = ref({ data: [] })
const loading = ref(false)
const showPaymentModal = ref(false)
const processingPayment = ref(false)
const paymentErrors = ref(null)

// Filters
const filters = reactive({
  start_date: '',
  end_date: '',
  type: 'all'
})

// Payment Form
const paymentForm = reactive({
  date: new Date().toISOString().split('T')[0],
  amount: '',
  payment_method: 'cash',
  reference_no: '',
  notes: ''
})

// Computed
const balanceSummary = computed(() => {
  if (!transactions.value.data) return {}

  const data = transactions.value.data
  const opening = supplier.value?.opening_balance || 0
  const totalDebit = data.reduce((sum, t) => sum + parseFloat(t.debit), 0)
  const totalCredit = data.reduce((sum, t) => sum + parseFloat(t.credit), 0)
  const currentBalance = opening + totalDebit - totalCredit

  return {
    opening_balance: opening,
    total_debit: totalDebit,
    total_credit: totalCredit,
    current_balance: currentBalance,
    running_balance: currentBalance
  }
})

const newBalanceAfterPayment = computed(() => {
  const current = parseFloat(supplier.value?.current_balance || 0)
  const payment = parseFloat(paymentForm.amount || 0)
  return current - payment // Payment reduces what we owe supplier
})

// Methods
const fetchSupplier = async () => {
  try {
    const response = await axios.get(`/suppliers/${supplierId}`)
    supplier.value = response.data.data.supplier
  } catch (error) {
    console.error('Error fetching supplier:', error)
  }
}

const fetchTransactions = async () => {
  loading.value = true
  try {
    const params = {
      ...filters,
      per_page: 20
    }

    const response = await axios.get(`/suppliers/${supplierId}/transactions`, { params })
    transactions.value = response.data.data
  } catch (error) {
    console.error('Error fetching transactions:', error)
    Swal.fire({
      title: 'Error',
      text: 'Failed to load transactions',
      icon: 'error'
    })
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  fetchTransactions(page)
}

const showPaymentModals = () => {
  paymentForm.date = new Date().toISOString().split('T')[0]
  paymentForm.amount = ''
  paymentForm.reference_no = ''
  paymentForm.notes = ''
  paymentErrors.value = null
  showPaymentModal.value = true
}

const submitPayment = async () => {
  processingPayment.value = true
  paymentErrors.value = null

  try {
    const response = await axios.post(`/suppliers/${supplierId}/payments`, paymentForm)

    if (response.data.success) {
      Swal.fire({
        title: 'Success!',
        text: 'Payment recorded successfully',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })

      closePaymentModal()
      fetchSupplier()
      fetchTransactions()
    }
  } catch (error) {
    if (error.response?.status === 422) {
      paymentErrors.value = error.response.data.errors
    } else {
      Swal.fire({
        title: 'Error',
        text: error.response?.data?.message || 'Failed to record payment',
        icon: 'error'
      })
    }
  } finally {
    processingPayment.value = false
  }
}

const getBalanceClass = (balance) => {
  const amount = parseFloat(balance)
  if (amount > 0) return 'text-danger' // We owe supplier
  if (amount < 0) return 'text-success' // Supplier owes us
  return 'text-muted'
}

const getBalanceText = (balance) => {
  const amount = parseFloat(balance)
  if (amount > 0) return 'We owe supplier'
  if (amount < 0) return 'Supplier owes us'
  return 'Settled'
}

const getTypeBadgeClass = (type) => {
  switch (type) {
    case 'purchase': return 'bg-primary'
    case 'payment': return 'bg-success'
    case 'opening_balance': return 'bg-info'
    case 'return': return 'bg-warning'
    default: return 'bg-secondary'
  }
}

const getTransactionRowClass = (type) => {
  switch (type) {
    case 'purchase': return 'purchase-row'
    case 'payment': return 'payment-row'
    default: return ''
  }
}

const formatType = (type) => {
  return type.replace('_', ' ').toUpperCase()
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-PK')
}

const formatCurrency = (amount) => {
  return parseFloat(amount || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const goBack = () => {
  router.push({ name: 'suppliers' })
}

const closePaymentModal = () => {
  showPaymentModal.value = false
}

// Lifecycle
onMounted(() => {
  fetchSupplier()
  fetchTransactions()
})
</script>

<style scoped>
.transactions-wrapper {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.header-section {
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 15px;
}

.avatar-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
}

.balance-summary {
  border-left: 3px solid #dee2e6;
  padding-left: 15px;
}

.purchase-row {
  background-color: rgba(13, 110, 253, 0.05);
}

.payment-row {
  background-color: rgba(25, 135, 84, 0.05);
}

.table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

@media (max-width: 768px) {
  .transactions-wrapper {
    padding: 10px;
  }

  .table-responsive {
    font-size: 14px;
  }
}
</style>
