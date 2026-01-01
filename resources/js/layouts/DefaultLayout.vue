<template>
<div class="layout-wrapper">
    <!-- Top Navbar -->
    <navbar
      v-if="showNavbar"
      @toggle-sidebar="toggleSidebar"
      @toggle-collapse="toggleCollapse"
      :sidebar-collapsed="sidebarCollapsed"
    />
    <div class="" :class="{ 'has-navbar': showNavbar }">
      <!-- Sidebar -->
      <CSidebar
        class="sidebar-fixed border-end"
        :visible="sidebarVisible"
        @visible-change="(val) => sidebarVisible = val"
      >
        <CSidebarHeader class="border-bottom d-flex justify-content-between align-items-center">
          <CSidebarBrand class="fs-5 fw-bold">
            <CIcon :icon="cilBox" class="me-2" />
            IMS-SZB
          </CSidebarBrand>
          <CButton @click="sidebarVisible = false">close</CButton>
        </CSidebarHeader>
        <CSidebarNav>
          <!-- Dashboard Link -->
          <CNavItem>
            <CNavLink :active="isActiveRoute('/')" @click="navigateTo('/')" class="d-flex align-items-center">
              <CIcon custom-class-name="nav-icon me-3" :icon="cilSpeedometer" />
              Dashboard
            </CNavLink>
          </CNavItem>

          <!-- Products Link -->
          <CNavItem>
            <CNavLink :active="isActiveRoute('/products-list')" @click="navigateTo('/products-list')" class="d-flex align-items-center">
              <CIcon custom-class-name="nav-icon me-3" :icon="cilStorage" />
              Products
            </CNavLink>
          </CNavItem>

          <!-- Stocks Link -->
          <CNavItem>
            <CNavLink :active="isActiveRoute('/stocks-list')" @click="navigateTo('/stocks-list')" class="d-flex align-items-center">
              <CIcon custom-class-name="nav-icon me-3" :icon="cilListRich" />
              Stock List
            </CNavLink>
          </CNavItem>

          <!-- Stock Report Link -->
          <CNavItem>
            <CNavLink :active="isActiveRoute('/stocks-report')" @click="navigateTo('/stocks-report')" class="d-flex align-items-center">
              <CIcon custom-class-name="nav-icon me-3" :icon="cilChart" />
              Stock Report
            </CNavLink>
          </CNavItem>

          <!-- Stock transaction Link -->
          <CNavItem>
            <CNavLink :active="isActiveRoute('/transaction-report')" @click="navigateTo('/transaction-report')" class="d-flex align-items-center">
              <CIcon custom-class-name="nav-icon me-3" :icon="cilChart" />
              Sales & Purchases Report
            </CNavLink>
          </CNavItem>
        </CSidebarNav>

        <!-- Sidebar Footer (Optional) -->
        <CSidebarFooter class="border-top p-3 d-none d-lg-block">
          <div class="small text-muted">
            © {{ currentYear }} IMS
          </div>
        </CSidebarFooter>
      </CSidebar>

      <!-- Main Content -->
      <main :class="{'full-width': !sidebarVisible }">
        <div class="content-wrapper">
          <router-view />
        </div>
      </main>
    </div>

    <!-- Login Page (No Layout) -->
    <div v-if="!showNavbar" class="auth-wrapper min-vh-100">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import navbar from '@/components/navbar.vue'

const route = useRoute()
const router = useRouter()

// State
const sidebarVisible = ref(true)
const sidebarUnfoldable = ref(false)
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
  if (window.innerWidth < 992) {
    sidebarVisible.value = false
  }
}

// Toggle mobile sidebar
const toggleSidebar = () => {
  sidebarVisible.value = !sidebarVisible.value
}

// Responsive handling
const handleResize = () => {
  if (window.innerWidth < 992) {
    sidebarVisible.value = false
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
  padding-top: 56px; /* Adjust to your navbar height */
}

.sidebar-fixed {
  width: 260px;
  position: fixed;
  top: 56px;
  left: 0;
  bottom: 0;
  background: #ffffff;
  z-index: 1030;
  transition: transform 0.3s ease;
  box-shadow: 2px 0 10px rgba(0,0,0,0.05);
  overflow: hidden;
}

main {
  margin-left: 260px;
  transition: margin-left 0.3s ease;
  background-color: #f8f9fa;
  min-height: calc(100vh - 56px);
}

.full-width {
  margin-left: 0 !important;
}

@media (max-width: 991.98px) {
  .sidebar-fixed {
    transform: translateX(-100%);
  }
  .sidebar-fixed[visible="true"] {
    transform: translateX(0);
  }
  main {
    margin-left: 0 !important;
  }
}

/* Nav Link Styling */
:deep(.nav-link) {
  padding: 0.875rem 1rem;
  border-radius: 0.5rem;
  margin: 0.25rem 0.5rem;
  color: #495057;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  cursor: pointer;
}

:deep(.nav-link:hover) {
  background-color: #e9ecef;
  color: #0d6efd;
}

/* Active state for CNavLink */
:deep(.nav-link.active) {
  background-color: rgba(13, 110, 253, 0.15);
  color: #0d6efd;
  font-weight: 600;
}

/* CoreUI's active class */
:deep(.nav-link[aria-current="page"]) {
  background-color: rgba(13, 110, 253, 0.15);
  color: #0d6efd;
  font-weight: 600;
}

.nav-icon {
  width: 20px;
  height: 20px;
  opacity: 0.8;
  margin-right: 12px;
  flex-shrink: 0;
}

.content-wrapper {
  padding: 2rem;
}

.auth-wrapper {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

/* Mobile overlay for sidebar */
@media (max-width: 991.98px) {
  .sidebar-overlay {
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1029;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
  }

  .sidebar-fixed[visible="true"] ~ .sidebar-overlay {
    opacity: 1;
    visibility: visible;
  }
}
</style>

<!-- Add this style block for better sidebar overlay -->
<style>
@media (max-width: 991.98px) {
  body:has(.sidebar-fixed[visible="true"]) {
    overflow: hidden;
  }
}

/* Smooth transitions for sidebar */
.sidebar-fixed {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Better active state indicator */
.nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: #0d6efd;
  border-radius: 0 4px 4px 0;
}
</style>
