<template>
    <div class="mx-2 w-full">
        <CCard>
            <CCardHeader class="d-flex justify-content-between align-items-center">
                <strong>Products List</strong>
                <router-link to="/products-create" class=" text-white">
                <CButton color="primary" :to="{ name: 'products.create' }">
                    <CIcon  :icon="cilSpeedometer" />
                        add product
                    </CButton>
                </router-link>
            </CCardHeader>
            <CCardBody>
                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <CInputGroup>
                            <CFormInput v-model="search" placeholder="Search products..." @input="debouncedSearch" />
                            <CInputGroupText>
                                <CIcon name="cil-search" />
                            </CInputGroupText>
                        </CInputGroup>
                    </div>
                    <div class="col-md-3">
                        <CFormSelect v-model="filters.type" @change="fetchProducts">
                            <option value="">All Types</option>
                            <option value="physical">Physical</option>
                            <option value="digital">Digital</option>
                            <option value="service">Service</option>
                        </CFormSelect>
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
                            <CTableHeaderCell>Type</CTableHeaderCell>
                            <CTableHeaderCell>Unit/Size</CTableHeaderCell>
                            <CTableHeaderCell>Min Limit</CTableHeaderCell>
                            <CTableHeaderCell>Purchase Price</CTableHeaderCell>
                            <CTableHeaderCell>Sale Price</CTableHeaderCell>
                            <CTableHeaderCell>Actions</CTableHeaderCell>
                        </CTableRow>
                    </CTableHead>
                    <CTableBody>
                        <CTableRow v-for="product in products" :key="product.id">
                            <CTableDataCell>{{ product.name }}</CTableDataCell>
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
                                    <CButton color="info" size="sm"
                                        :to="{ name: 'products.edit', params: { id: product.id } }">
                                        <router-link :to="{ name: 'products.edit', params: { id: product.id } }">
                                            <CIcon name="cil-pencil" />

                                        </router-link>
                                    </CButton>
                                    <CButton color="danger" size="sm" @click="confirmDelete(product)">
                                        <CIcon name="cil-trash" />
                                    </CButton>
                                </CButtonGroup>
                            </CTableDataCell>
                        </CTableRow>
                    </CTableBody>
                </CTable>

                <!-- Pagination -->
                <div v-if="meta" class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ meta.from }} to {{ meta.to }} of {{ meta.total }} entries
                    </div>
                    <CPagination>
                        <CPaginationItem :disabled="!links.prev" @click="fetchProducts(meta.current_page - 1)">
                            Previous
                        </CPaginationItem>
                        <CPaginationItem v-for="page in pages" :key="page" :active="page === meta.current_page"
                            @click="fetchProducts(page)">
                            {{ page }}
                        </CPaginationItem>
                        <CPaginationItem :disabled="!links.next" @click="fetchProducts(meta.current_page + 1)">
                            Next
                        </CPaginationItem>
                    </CPagination>
                </div>

                <!-- Empty State -->
                <div v-if="!loading && products.length === 0" class="text-center py-5">
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

        <!-- Delete Confirmation Modal -->
        <CModal :visible="showDeleteModal" @close="showDeleteModal = false">
            <CModalHeader>
                <CModalTitle>Confirm Delete</CModalTitle>
            </CModalHeader>
            <CModalBody>
                Are you sure you want to delete product "<strong>{{ productToDelete?.name }}</strong>"?
                This action cannot be undone.
            </CModalBody>
            <CModalFooter>
                <CButton color="secondary" @click="showDeleteModal = false">Cancel</CButton>
                <CButton color="danger" @click="deleteProduct" :disabled="deleting">
                    <CSpinner v-if="deleting" component="span" size="sm" />
                    {{ deleting ? 'Deleting...' : 'Delete' }}
                </CButton>
            </CModalFooter>
        </CModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import debounce from 'lodash/debounce'

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

import { cilPlus, cilPencil, cilTrash, cilInbox } from '@coreui/icons'
import { cilSearch } from '@coreui/icons'

const products = ref([])
const loading = ref(true)
const search = ref('')
const filters = reactive({
    type: ''
})
const meta = ref(null)
const links = ref({})
const showDeleteModal = ref(false)
const productToDelete = ref(null)
const deleting = ref(false)

// Fetch products
const fetchProducts = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            search: search.value,
            type: filters.type
        }

        const response = await axios.get('/products', { params })
        products.value = response.data.data
        meta.value = response.data.meta
        links.value = response.data.links
    } catch (error) {
        console.error('Error fetching products:', error)
    } finally {
        loading.value = false
    }
}

// Debounced search
const debouncedSearch = debounce(() => {
    fetchProducts()
}, 500)

// Pagination pages
const pages = computed(() => {
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

// Type color
const getTypeColor = (type) => {
    const colors = {
        physical: 'primary',
        digital: 'success',
        service: 'warning'
    }
    return colors[type] || 'secondary'
}

// Delete product
const confirmDelete = (product) => {
    productToDelete.value = product
    showDeleteModal.value = true
}

const deleteProduct = async () => {
    deleting.value = true
    try {
        await axios.delete(`/products/${productToDelete.value.id}`)
        console.log(meta.value)
        fetchProducts(1)
        showDeleteModal.value = false
    } catch (error) {
        console.error('Error deleting product:', error)
    } finally {
        deleting.value = false
    }
}

// Lifecycle
onMounted(() => {
    fetchProducts()
})
</script>
