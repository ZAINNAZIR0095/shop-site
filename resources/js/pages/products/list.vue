<template>
    <div class="mx-2 w-full">
        <CCard>
            <CCardHeader class="d-flex justify-content-between align-items-center">
                <strong>Products List</strong>
                <router-link to="/products-create" class=" text-white">
                    <CButton color="primary" :to="{ name: 'products.create' }">
                        <CIcon :icon="cilSpeedometer" />
                        add product
                    </CButton>
                </router-link>
            </CCardHeader>
            <CCardBody>
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <CInputGroup>
                            <CFormInput v-model="searchTerm" placeholder="Search products..." />
                            <CInputGroupText>
                                <CIcon name="cil-search" />
                            </CInputGroupText>
                        </CInputGroup>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <CSpinner />
                </div>

                <!-- Products Table -->
                <CTable v-else striped hover responsive>
                    <CTableHead>
                        <CTableRow>
                            <CTableHeaderCell>Name</CTableHeaderCell>
                            <CTableHeaderCell>Model No</CTableHeaderCell>
                            <CTableHeaderCell>Type</CTableHeaderCell>
                            <CTableHeaderCell>Unit/Size</CTableHeaderCell>
                            <CTableHeaderCell>Min Limit</CTableHeaderCell>
                            <CTableHeaderCell>Purchase Price</CTableHeaderCell>
                            <CTableHeaderCell>Sale Price</CTableHeaderCell>
                            <CTableHeaderCell>Actions</CTableHeaderCell>
                        </CTableRow>
                    </CTableHead>
                    <CTableBody>
                        <CTableRow v-for="product in paginatedProducts" :key="product.id">
                            <CTableDataCell>{{ product.name }}</CTableDataCell>
                            <CTableDataCell>{{ product.model_no || '' }}</CTableDataCell>
                            <CTableDataCell>
                                <CBadge :color="getTypeColor(product.type)">
                                    {{ product.type }}
                                </CBadge>
                            </CTableDataCell>
                            <CTableDataCell>
                                <span v-if="product.unit">{{ product.unit }}</span>
                                <span v-if="product.unit && product.size"> / </span>
                                <span v-if="product.size">{{ product.size }}</span>
                            </CTableDataCell>
                            <CTableDataCell>
                                <CBadge :color="product.min_limit > 0 ? 'warning' : 'secondary'">
                                    {{ product.min_limit }}
                                </CBadge>
                            </CTableDataCell>
                            <CTableDataCell>{{ product.purchase_price }}</CTableDataCell>
                            <CTableDataCell>
                                <strong>{{ product.sale_price }}</strong>
                            </CTableDataCell>
                            <CTableDataCell>
                                <CButtonGroup>
                                    <router-link :to="{ name: 'products.edit', params: { id: product.id } }"
                                        style="text-decoration: none;">
                                        <CButton color="info" size="sm">
                                            <CIcon name="cil-pencil" />
                                        </CButton>
                                    </router-link>
                                    <CButton :color="product.status === 'active' ? 'warning' : 'success'" size="sm"
                                        @click="confirmToggleStatus(product)">
                                        <CIcon :icon="product.status === 'active' ? cilBan : cilCheckCircle" />
                                        {{ product.status === 'active' ? 'Disable' : 'Enable' }}
                                    </CButton>
                                </CButtonGroup>
                            </CTableDataCell>
                        </CTableRow>
                    </CTableBody>
                </CTable>

                <!-- Pagination -->
                <div v-if="allProducts.length > 0" class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ filteredProducts.length }} entries
                    </div>
                    <CPagination>
                        <CPaginationItem :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
                            Previous
                        </CPaginationItem>
                        <CPaginationItem v-for="page in pages" :key="page" :active="page === currentPage"
                            @click="changePage(page)">
                            {{ page }}
                        </CPaginationItem>
                        <CPaginationItem :disabled="currentPage === totalPages" @click="changePage(currentPage + 1)">
                            Next
                        </CPaginationItem>
                    </CPagination>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && allProducts.length === 0" class="text-center py-5">
                    <CIcon name="cil-inbox" size="3xl" class="text-muted mb-3" />
                    <h5>No products found</h5>
                    <p>Get started by creating your first product</p>
                    <CButton color="primary" to="/products-create">
                        <router-link to="/products-create" class="nav-link">
                            <CIcon custom-class-name="nav-icon" :icon="cilSpeedometer" />
                            create product
                        </router-link>
                    </CButton>
                </div>
            </CCardBody>
        </CCard>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

// CoreUI Components
import {
    CCard, CCardHeader, CCardBody,
    CTable, CTableHead, CTableBody, CTableRow, CTableHeaderCell, CTableDataCell,
    CButton, CButtonGroup, CSpinner,
    CInputGroup, CFormInput, CInputGroupText, CFormSelect,
    CBadge, CPagination, CPaginationItem,
    CModal, CModalHeader, CModalTitle, CModalBody, CModalFooter
} from '@coreui/vue'
import { cilSpeedometer } from '@coreui/icons'
import { cilBan, cilCheckCircle } from '@coreui/icons'
import { cilPencil, cilInbox } from '@coreui/icons'
import { cilSearch } from '@coreui/icons'

const allProducts = ref([]) // Store all loaded products
const loading = ref(true)
const searchTerm = ref('')
const filters = reactive({
    type: ''
})

// Pagination variables
const itemsPerPage = 10
const currentPage = ref(1)

// Fetch all products once
const fetchAllProducts = async () => {
    loading.value = true
    try {
        const response = await axios.get('/products')
        allProducts.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching products:', error)
    } finally {
        loading.value = false
    }
}

// Computed: Filter products based on search term
const filteredProducts = computed(() => {
    if (!searchTerm.value && !filters.type) {
        return allProducts.value
    }

    return allProducts.value.filter(product => {
        const matchesSearch = !searchTerm.value ||
            product.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (product.unit && product.unit.toLowerCase().includes(searchTerm.value.toLowerCase())) ||
            (product.type && product.type.toLowerCase().includes(searchTerm.value.toLowerCase()))

        const matchesType = !filters.type || product.type === filters.type

        return matchesSearch && matchesType
    })
})

// Computed: Paginated products
const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return filteredProducts.value.slice(start, end)
})

// Computed: Pagination info
const totalPages = computed(() => {
    return Math.ceil(filteredProducts.value.length / itemsPerPage)
})

const startIndex = computed(() => {
    return (currentPage.value - 1) * itemsPerPage
})

const endIndex = computed(() => {
    const end = startIndex.value + itemsPerPage
    return end > filteredProducts.value.length ? filteredProducts.value.length : end
})

const pages = computed(() => {
    const total = totalPages.value
    const current = currentPage.value
    const delta = 2
    const range = []

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        range.push(i)
    }

    if (current - delta > 2) range.unshift('...')
    if (current + delta < total - 1) range.push('...')

    range.unshift(1)
    if (total > 1) range.push(total)

    return range
})

// Methods
const changePage = (page) => {
    if (page === '...') return
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

const getTypeColor = (type) => {
    const colors = {
        physical: 'primary',
        digital: 'success',
        service: 'warning'
    }
    return colors[type] || 'secondary'
}

const toggleStatus = async (product) => {
    const newStatus = product.status === 'active' ? 'inactive' : 'active'
    try {
        const response = await axios.put(`/products/${product.id}/status`, { status: newStatus })
        if (response.data.success) {
            // Update the product status in the local array
            const index = allProducts.value.findIndex(p => p.id === product.id)
            if (index !== -1) {
                allProducts.value[index].status = newStatus
            }

            Swal.fire({
                title: 'Success',
                text: `Product ${newStatus === 'active' ? 'enabled' : 'disabled'} successfully!`,
                icon: 'success'
            })
        }
    } catch (error) {
        console.error('Error toggling status:', error)
        Swal.fire({
            title: 'Update Failed',
            text: 'Failed to update product status',
            icon: 'error'
        })
    }
}

const confirmToggleStatus = async (product) => {
    const action = product.status === 'active' ? 'disable' : 'enable'
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: `Are you sure you want to ${action} this product?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    })

    if (result.isConfirmed) {
        toggleStatus(product)
    }
}

// Watch for search/filter changes and reset to page 1
watch([searchTerm, () => filters.type], () => {
    currentPage.value = 1
})

// Lifecycle
onMounted(() => {
    fetchAllProducts()
})
</script>
