        <template>
            <div class="layout-wrapper">
                <!-- Top Navbar -->
                <navbar v-if="showNavbar" @toggle-sidebar="toggleSidebar" @toggle-collapse="toggleCollapse"
                    :sidebar-collapsed="sidebarCollapsed" />

                <div :class="{ 'has-navbar': showNavbar }">
                    <!-- Sidebar -->
                    <CSidebar class="sidebar-fixed border-end" :visible="sidebarVisible" :unfoldable="sidebarCollapsed"
                        @visible-change="(val) => sidebarVisible = val">
                        <CSidebarHeader class="border-bottom d-flex justify-content-between align-items-center">
                            <CSidebarBrand class="fs-5 fw-bold">
                                <CIcon :icon="cilBox" class="me-2" />
                                <span v-if="!sidebarCollapsed">IMS-SZB</span>
                            </CSidebarBrand>
                            <CButton @click="sidebarVisible = false" class="d-lg-none" size="sm">
                                <CIcon :icon="cilX" />
                            </CButton>
                        </CSidebarHeader>
                        <CSidebarNav v-if="currentUser.email == 'staff@ims.com'">

                            <!-- Stocks Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/stocks-list')" @click="navigateTo('/stocks-list')"
                                    class="d-flex align-items-center  px-0" :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilListRich" />
                                    <span>Stock List</span>
                                </CNavLink>
                            </CNavItem>
                        </CSidebarNav>

                        <CSidebarNav v-else>
                            <!-- Dashboard Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/')" @click="navigateTo('/')"
                                    class="d-flex align-items-center px-0" :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilSpeedometer" />
                                    <span>Dashboard</span>
                                </CNavLink>
                            </CNavItem>

                            <!-- Products Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/products-list')"
                                    @click="navigateTo('/products-list')" class="d-flex align-items-center  px-0"
                                    :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilStorage" />
                                    <span>Products</span>
                                </CNavLink>
                            </CNavItem>

                            <!-- Stocks Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/stocks-list')" @click="navigateTo('/stocks-list')"
                                    class="d-flex align-items-center  px-0" :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilListRich" />
                                    <span>Stock List</span>
                                </CNavLink>
                            </CNavItem>

                            <!-- Stock Report Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/stocks-report')"
                                    @click="navigateTo('/stocks-report')" class="d-flex align-items-center  px-0"
                                    :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilChart" />
                                    <span>Stock Report</span>
                                </CNavLink>
                            </CNavItem>

                            <!-- Stock transaction Link -->
                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/transaction-report')"
                                    @click="navigateTo('/transaction-report')" class="d-flex align-items-center  px-0"
                                    :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilChart" />
                                    <span>Sales & Purchases</span>
                                </CNavLink>
                            </CNavItem>

                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/customers-list')"
                                    @click="navigateTo('/customers-list')" class="d-flex align-items-center  px-0"
                                    :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilPeople" />
                                    <span>Customers</span>
                                </CNavLink>
                            </CNavItem>

                            <CNavItem>
                                <CNavLink :active="isActiveRoute('/suppliers-list')"
                                    @click="navigateTo('/suppliers-list')" class="d-flex align-items-center  px-0"
                                    :class="sidebarCollapsed ? 'px-0' : 'px-2'">
                                    <CIcon custom-class-name="nav-icon me-2" :icon="cilPeople" />
                                    <span>suppliers</span>
                                </CNavLink>
                            </CNavItem>

                        </CSidebarNav>

                        <!-- Sidebar Footer -->
                        <CSidebarFooter class="border-top p-3 d-none d-lg-block">
                            <div class="small text-muted text-center">
                                © {{ currentYear }} IMS
                            </div>
                        </CSidebarFooter>
                    </CSidebar>

                    <!-- Mobile Overlay -->
                    <div v-if="sidebarVisible && isMobile" class="sidebar-overlay" @click="sidebarVisible = false">
                    </div>

                    <!-- Main Content -->
                    <main :class="{
                        'full-width': !sidebarVisible,
                        'sidebar-collapsed': sidebarCollapsed,
                        'sidebar-expanded': !sidebarCollapsed
                    }">
                        <div class="content-wrapper">
                            <router-view />
                        </div>
                    </main>
                </div>

                <!-- Login Page (No Layout) -->
                <!-- <div v-if="!showNavbar" class="auth-wrapper">
                    <router-view />
                </div> -->
            </div>
        </template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
    cilSpeedometer,
    cilStorage,
    cilListRich,
    cilChart,
    cilPeople,
    cilX
} from '@coreui/icons'
import { CIcon } from '@coreui/icons-vue'

import navbar from '@/components/navbar.vue'
import { useAuthStore } from '../stores/authStore'
const auth = useAuthStore()
const currentUser = computed(() => auth.currentUser)
console.log(currentUser.value)

const route = useRoute()
const router = useRouter()

// State
const sidebarVisible = ref(true)
const sidebarCollapsed = ref(false)
const isMobile = ref(false)
const currentYear = new Date().getFullYear()

// Show layout only on authenticated routes
const showNavbar = computed(() => route.path !== '/login')

// Check if route is active
const isActiveRoute = (path) => {
    if (path === '/') {
        return route.path === '/'
    }
    return route.path.startsWith(path)
}

// Navigate function
const navigateTo = (path) => {
    router.push(path)
    // Close sidebar on mobile after navigation
    if (isMobile.value) {
        sidebarVisible.value = false
    }
}

// Toggle mobile sidebar
const toggleSidebar = () => {
    sidebarVisible.value = !sidebarVisible.value
}

// Toggle collapse (desktop)
const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
}

// Responsive handling
const handleResize = () => {
    const width = window.innerWidth
    isMobile.value = width < 992

    if (isMobile.value) {
        sidebarVisible.value = false
        sidebarCollapsed.value = false // Reset on mobile
    } else {
        sidebarVisible.value = true
    }
}

onMounted(() => {
    handleResize()
    window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.layout-wrapper {
    min-height: 100vh;
}

.has-navbar {
    padding-top: 56px;
    /* Navbar height */
}

/* Sidebar Styles */
.sidebar-fixed {
    width: 260px;
    position: fixed;
    top: 56px;
    left: 0;
    bottom: 0;
    background: #ffffff;
    z-index: 1035;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    overflow-y: auto;
}

/* Collapsed sidebar */
.sidebar-fixed[unfoldable="true"] {
    width: 70px;
}

/* Main Content */
main {
    margin-left: 260px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: #f8f9fa;
    min-height: calc(100vh - 56px);
    padding: 20px;
}

/* Collapsed content */
main.sidebar-collapsed {
    margin-left: 70px;
}

/* Full width when sidebar is hidden */
main.full-width {
    margin-left: 0 !important;
}

/* Mobile overlay */
.sidebar-overlay {
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1034;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

/* Responsive for mobile */
@media (max-width: 991.98px) {
    .sidebar-fixed {
        transform: translateX(-100%);
        width: 280px;
    }

    .sidebar-fixed[visible="true"] {
        transform: translateX(0);
    }

    main {
        margin-left: 0 !important;
        padding: 15px;
    }

    main.sidebar-collapsed {
        margin-left: 0 !important;
    }
}

/* Nav Link Styling */
:deep(.nav-link) {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin: 0.25rem 0.5rem;
    color: #495057;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
}

:deep(.nav-link:hover) {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

:deep(.nav-link.active),
:deep(.nav-link[aria-current="page"]) {
    background-color: rgba(13, 110, 253, 0.15);
    color: #0d6efd;
    font-weight: 600;
}

router .nav-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* Collapsed sidebar styles */
.sidebar-fixed[unfoldable="true"] :deep(.nav-link) {
    justify-content: center;
    padding: 0.75rem;
}

.sidebar-fixed[unfoldable="true"] :deep(.nav-link span) {
    display: none;
}

.sidebar-fixed[unfoldable="true"] :deep(.nav-link) .nav-icon {
    margin-right: 0;
}

/* Content wrapper */
.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

/* Auth pages */
.auth-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* Custom scrollbar */
.sidebar-fixed::-webkit-scrollbar {
    width: 6px;
}

.sidebar-fixed::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.sidebar-fixed::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.sidebar-fixed::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Small screens */
@media (max-width: 575.98px) {
    main {
        padding: 15px 10px !important;
    }

    .content-wrapper {
        padding: 0;
    }

    .auth-wrapper {
        padding: 15px;
    }
}
</style>

<!-- Global styles -->
<style>
/* Prevent body scrolling when sidebar is open on mobile */
.sidebar-overlay {
    display: none;
}

@media (max-width: 991.98px) {
    body.sidebar-open {
        overflow: hidden;
    }

    .sidebar-overlay {
        display: block;
    }
}
</style>
