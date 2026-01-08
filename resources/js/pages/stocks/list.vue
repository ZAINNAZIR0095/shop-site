    <!-- resources/js/views/stocks/StocksList.vue -->
    <template>
        <div>
            <!-- Header with filters and actions -->
            <CCard class="mb-4">
                <CCardBody>
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <!-- Title -->
                        <div>
                            <h1 class="text-h5 mb-0">Stock Management</h1>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex flex-wrap gap-2">
                            <router-link :to="{ name: 'stocks.create' }">
                                <CButton color="primary" class="text-white" :to="{ name: 'stocks.create' }">
                                    New Stock Entry
                                    <CIcon :icon="cilPlus" class="me-2" />
                                </CButton>
                            </router-link>

                            <!-- Reports Dropdown -->
                            <CDropdown>
                                <CDropdownToggle color="info">
                                    <CIcon class="me-2" />
                                    Reports
                                </CDropdownToggle>
                                <CDropdownMenu>
                                    <CDropdownItem @click="dailySummary">
                                        Daily Summary
                                    </CDropdownItem>
                                    <CDropdownItem @click="weeklySummary">>
                                        Weekly Summary
                                    </CDropdownItem>
                                    <CDropdownItem @click="monthlySummary">>
                                        Monthly Summary
                                    </CDropdownItem>
                                </CDropdownMenu>
                            </CDropdown>
                        </div>
                    </div>
                </CCardBody>
            </CCard>

            <!-- Filters Card -->
            <CCard class="mb-4">
                <CCardBody>
                    <CRow class="g-3">
                        <!-- Stock Type Filter -->
                        <CCol :md="3">
                            <CFormSelect v-model="filters.stock_type" :options="stockTypeOptions" label="Stock Type"
                                @update:modelValue="fetchStocks">
                                <option value="">All Types</option>
                                <option value="purchase">Purchase</option>
                                <option value="sale">Sale</option>
                            </CFormSelect>
                        </CCol>

                        <!-- Date Range -->
                        <CCol :md="3">
                            <CFormInput v-model="filters.start_date" type="date" label="From Date"
                                @update:modelValue="fetchStocks" />
                        </CCol>

                        <CCol :md="3">
                            <CFormInput v-model="filters.end_date" type="date" label="To Date"
                                @update:modelValue="fetchStocks" />
                        </CCol>

                        <!-- Party Name Search -->
                        <CCol :md="3">
                            <CFormInput v-model="filters.party_name" label="Customer/Supplier"
                                placeholder="Search by name..." @input="debounceSearch" />
                        </CCol>
                    </CRow>
                </CCardBody>
            </CCard>

            <!-- Main Content Card -->
            <CCard>
                <CCardBody>
                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-6">
                        <CSpinner />
                        <p class="mt-2">Loading stocks...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="stocks.length === 0" class="text-center py-6">
                        <h5 class="text-h5">No Stock Records Found</h5>
                        <p class="text-body-1 text-medium-emphasis mb-4">
                            Get started by creating your first stock entry
                        </p>
                        <CButton color="primary" :to="{ name: 'stocks.create' }">
                            Create Stock Entry
                        </CButton>
                    </div>

                    <!-- Stocks Table -->
                    <div v-else>
                        <CTable hover responsive class="mb-0">
                            <CTableHead>
                                <CTableRow>
                                    <CTableHeaderCell>Date</CTableHeaderCell>
                                    <CTableHeaderCell>Type</CTableHeaderCell>
                                    <CTableHeaderCell>Party Name</CTableHeaderCell>
                                    <CTableHeaderCell>Items</CTableHeaderCell>
                                    <CTableHeaderCell>Total Quantity</CTableHeaderCell>
                                    <CTableHeaderCell>Net Price</CTableHeaderCell>
                                    <CTableHeaderCell>Created By</CTableHeaderCell>
                                    <CTableHeaderCell class="text-end">Actions</CTableHeaderCell>
                                </CTableRow>
                            </CTableHead>
                            <CTableBody>
                                <CTableRow v-for="stock in stocks" :key="stock.id">
                                    <!-- Date -->
                                    <CTableDataCell>
                                        {{ formatDate(stock.date) }}
                                    </CTableDataCell>

                                    <!-- Type with badge -->
                                    <CTableDataCell>
                                        <CBadge :color="getStockTypeColor(stock.stock_type)">
                                            {{ formatStockType(stock.stock_type) }}
                                        </CBadge>
                                    </CTableDataCell>

                                    <!-- Party Name -->
                                    <CTableDataCell>
                                        <div>
                                            <strong>{{ stock.party_name || 'N/A' }}</strong>
                                            <div v-if="stock.party_phone" class="text-small text-muted">
                                                {{ stock.party_phone }}
                                            </div>
                                        </div>
                                    </CTableDataCell>

                                    <!-- Items -->
                                    <CTableDataCell>
                                        <div class="product-list">
                                            <div v-for="detail in stock.details.slice(0, 2)" :key="detail.id"
                                                class="mb-1">
                                                <small>
                                                    {{ detail.product?.name }} × {{ detail.quantity }}
                                                    <span class="text-muted">{{ detail.product?.unit }}</span>
                                                </small>
                                            </div>
                                            <div v-if="stock.details.length > 2" class="text-muted">
                                                +{{ stock.details.length - 2 }} more items
                                            </div>
                                        </div>
                                    </CTableDataCell>

                                    <!-- Total Quantity -->
                                    <CTableDataCell>
                                        {{ calculateTotalQuantity(stock.details) }}
                                    </CTableDataCell>

                                    <!-- Net Price -->
                                    <CTableDataCell>
                                        <strong class="text-primary">PKR {{ formatCurrency(stock.net_price) }}</strong>
                                    </CTableDataCell>

                                    <!-- Created By -->
                                    <CTableDataCell>
                                        <div class="text-small">
                                            {{ stock.user?.name || 'System' }}
                                        </div>
                                        <div class="text-small text-muted">
                                            {{ formatDateTime(stock.created_at) }}
                                        </div>
                                    </CTableDataCell>

                                    <!-- Actions -->
                                    <CTableDataCell class="text-end">
                                        <CButtonGroup>
                                            <router-link :to="{ name: 'stocks.show', params: { id: stock.id } }">
                                                <CButton size="sm" color="info"
                                                    :to="{ name: 'stocks.show', params: { id: stock.id } }"
                                                    title="View">

                                                    <CIcon :icon="cilFullscreen" />
                                                </CButton>
                                            </router-link>
                                            <router-link :to="{ name: 'stocks.edit', params: { id: stock.id } }">
                                                <CButton color="warning" size="sm">
                                                    <CIcon :icon="cilPencil" />
                                                </CButton>
                                            </router-link>
                                            <CButton size="sm" color="danger" @click="confirmDelete(stock)"
                                                title="Delete">
                                                <CIcon :icon="cilTrash" />
                                            </CButton>
                                        </CButtonGroup>
                                    </CTableDataCell>
                                </CTableRow>
                            </CTableBody>
                        </CTable>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Showing {{ meta?.from || 0 }} to {{ meta?.to || 0 }} of {{ meta?.total || 0 }} entries
                            </div>
                            <CPagination v-if="meta && meta.last_page > 1">
                                <CPaginationItem :disabled="!links.prev" @click="changePage(meta.current_page - 1)">
                                    Previous
                                </CPaginationItem>

                                <CPaginationItem v-for="page in visiblePages" :key="page"
                                    :active="page === meta.current_page" @click="changePage(page)">
                                    {{ page }}
                                </CPaginationItem>

                                <CPaginationItem :disabled="!links.next" @click="changePage(meta.current_page + 1)">
                                    Next
                                </CPaginationItem>
                            </CPagination>
                        </div>
                    </div>
                </CCardBody>
            </CCard>

            <!-- Delete Confirmation Modal -->
            <CModal :visible="showDeleteModal" @close="showDeleteModal = false">
                <CModalHeader>
                    <CModalTitle>Confirm Delete</CModalTitle>
                </CModalHeader>
                <CModalBody>
                    <p>Are you sure you want to delete this stock entry?</p>
                    <div v-if="stockToDelete" class="alert alert-warning">
                        <strong>{{ formatStockType(stockToDelete.stock_type) }}</strong>
                        <br>
                        Date: {{ formatDate(stockToDelete.date) }}
                        <br>
                        Party: {{ stockToDelete.party_name || 'N/A' }}
                        <br>
                        Amount: PKR {{ formatCurrency(stockToDelete.net_price) }}
                    </div>
                    <p class="text-danger mt-2">
                        <CIcon :icon="cil - warning" class="me-1" />
                        This action cannot be undone.
                    </p>
                </CModalBody>
                <CModalFooter>
                    <CButton color="secondary" @click="showDeleteModal = false">Cancel</CButton>
                    <CButton color="danger" @click="deleteStock" :disabled="deleting">
                        <CSpinner v-if="deleting" size="sm" class="me-2" />
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </CButton>
                </CModalFooter>
            </CModal>
        </div>
    </template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'
import { CIcon } from '@coreui/icons-vue'
import { cilEyedropper } from '@coreui/icons'
import Swal from 'sweetalert2'
import { cilPencil, cilTrash, cilSortAlphaDown, cilPlus, cilFullscreen } from '@coreui/icons'

const router = useRouter()

// Data
const stocks = ref([])
const loading = ref(true)
const deleting = ref(false)
const showDeleteModal = ref(false)
const stockToDelete = ref(null)
const meta = ref(null)
const links = ref({})

// Filters
const filters = reactive({
    stock_type: '',
    start_date: '',
    end_date: '',
    party_name: ''
})

// Stock type options for select
const stockTypeOptions = [
    { value: '', label: 'All Types' },
    { value: 'purchase', label: 'Purchase' },
    { value: 'sale', label: 'Sale' },
]

// Methods
const fetchStocks = async (page = 1) => {
    loading.value = true

    try {
        const params = {
            page,
            ...filters
        }

        // Remove empty filters
        Object.keys(params).forEach(key => {
            if (!params[key]) delete params[key]
        })

        const response = await axios.get('/stocks', { params })

        if (response.data.success) {
            const paginatedData = response.data.data;

            stocks.value = paginatedData.data;                    // the stock records
            meta.value = {
                current_page: paginatedData.current_page,
                last_page: paginatedData.last_page,
                from: paginatedData.from,
                to: paginatedData.to,
                total: paginatedData.total,
                per_page: paginatedData.per_page,
            };
            links.value = {
                prev: paginatedData.prev_page_url,
                next: paginatedData.next_page_url,
            };
        }
        console.log(response.data)
    } catch (error) {
        console.error('Error fetching stocks:', error)
    } finally {
        loading.value = false
    }
}
const dailySummary = async (page = 1) => {
    loading.value = true

    try {
        const today = new Date();

        filters.start_date = formatDate(today);
        filters.end_date = formatDate(today);
        fetchStocks();
    } catch (error) {
        console.error('Error fetching stocks:', error)
    } finally {
        loading.value = false
    }
}
const weeklySummary = async (page = 1) => {
    loading.value = true;

    try {
        const today = new Date();
        const lastWeek = new Date();

        lastWeek.setDate(today.getDate() - 6); // last 7 days

        filters.start_date = formatDate(lastWeek);
        filters.end_date = formatDate(today);

        fetchStocks();
    } catch (error) {
        console.error('Error fetching stocks:', error);
    } finally {
        loading.value = false;
    }
};

const monthlySummary = async (page = 1) => {
    loading.value = true;

    try {
        const today = new Date();

        const firstDayOfMonth = new Date(
            today.getFullYear(),
            today.getMonth(),
            1
        );

        filters.start_date = formatDate(firstDayOfMonth);
        filters.end_date = formatDate(today);

        fetchStocks();
    } catch (error) {
        console.error('Error fetching stocks:', error);
    } finally {
        loading.value = false;
    }
};



const debounceSearch = debounce(() => {
    fetchStocks()
}, 500)

const changePage = (page) => {
    if (page >= 1 && page <= meta.value.last_page) {
        fetchStocks(page)
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const confirmDelete = (stock) => {
    stockToDelete.value = stock
    showDeleteModal.value = true
}

const deleteStock = async () => {
    deleting.value = true

    try {
        await axios.delete(`/stocks/${stockToDelete.value.id}`)

        // Refresh the list
        fetchStocks(meta.value.current_page)

        showDeleteModal.value = false
        stockToDelete.value = null
    } catch (error) {
        console.error('Error deleting stock:', error)
        if (!response.data.success) {
            Swal.fire({
                title: 'Delete Failed',
                text: 'Failed to delete stock record',
                icon: 'error'
            })
        }
    } finally {
        deleting.value = false
    }
}

// Helper methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-PK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('en-PK', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatStockType = (type) => {
    const types = {
        purchase: 'Purchase',
        sale: 'Sale',
    }
    return types[type] || type
}

const getStockTypeColor = (type) => {
    const colors = {
        purchase: 'success',
        sale: 'info',
    }
    return colors[type] || 'primary'
}

const formatCurrency = (amount) => {
    return parseFloat(amount || 0).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const calculateTotalQuantity = (details) => {
    return details.reduce((sum, detail) => sum + detail.quantity, 0)
}

// Computed properties
const visiblePages = computed(() => {
    if (!meta.value) return []

    const current = meta.value.current_page
    const last = meta.value.last_page
    const delta = 2
    const range = []

    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i)
    }

    if (current - delta > 2) range.unshift('...')
    if (current + delta < last - 1) range.push('...')

    range.unshift(1)
    if (last > 1) range.push(last)

    return range
})

// Lifecycle
onMounted(() => {
    fetchStocks()
})
</script>

<style scoped>
.product-list {
    max-width: 200px;
}

.text-small {
    font-size: 0.875rem;
}
</style>
