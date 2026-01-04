<template>
  <header :class="{ 'sidebar-collapsed': sidebarCollapsed }">
    <CNavbar color-scheme="light" class="app-header" placement="fixed-top">
      <CContainer fluid class="d-flex align-items-center">
        <!-- Sidebar Toggle Buttons -->
        <div class="d-flex align-items-center me-3">
          <!-- Mobile toggle (hamburger) -->
          <CButton
            color="light"
            class="d-lg-none me-2"
            @click="$emit('toggle-sidebar')"
          >
            <CIcon :icon="cilMenu" />
          </CButton>

          <!-- Desktop collapse toggle -->
          <CButton
  color="light"
  class="d-none d-lg-inline-block me-3"
  @click="$emit('toggle-collapse')"
>
  <CIcon
    :icon="sidebarCollapsed ? cilChevronDoubleRight : cilChevronDoubleLeft"
    size="lg"
  />
</CButton>
        </div>

        <!-- Brand -->
        <CNavbarBrand href="#" class="d-flex align-items-center">
          <CIcon :icon="cilInventory" height="32" class="me-2" />
          <span class="d-none d-md-inline">Inventory System</span>
        </CNavbarBrand>

        <div class="flex-grow-1"></div>

        <!-- User Dropdown -->
        <CNavbarNav>
          <CDropdown variant="nav-item">
            <CDropdownToggle color="secondary" class="py-0 px-2 d-flex align-items-center">
              <CAvatar :text="userInitials" size="md" class="me-2" />
              <span class="d-none d-md-inline">{{ authStore.currentUser?.name || 'User' }}</span>
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem @click="handleLogout">
                <CIcon :icon="cilAccountLogout" class="me-2" />
                Logout
              </CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </CNavbarNav>
      </CContainer>
    </CNavbar>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import {
  CNavbar, CContainer, CButton, CNavbarBrand,
  CNavbarNav, CDropdown, CDropdownToggle, CDropdownMenu,
  CDropdownItem, CAvatar
} from '@coreui/vue'
import { cilMenu } from '@coreui/icons'
import { cilChevronLeft, cilChevronRight } from '@coreui/icons'
import {
  cilChevronDoubleLeft,
  cilChevronDoubleRight
} from '@coreui/icons'
import { CIcon } from '@coreui/icons-vue'

const router = useRouter()
const authStore = useAuthStore()

defineProps(['sidebarCollapsed'])
defineEmits(['toggle-sidebar', 'toggle-collapse'])

const userInitials = computed(() => {
  if (!authStore.currentUser?.name) return 'U'
  return authStore.currentUser.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.app-header {
  height: 56px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1040;
}

/* Adjust navbar position based on sidebar state */
@media (min-width: 992px) {
  .sidebar-collapsed .app-header {
    left: 70px !important;
  }

  .app-header {
    left: 260px !important;
    right: 0 !important;
  }
}

/* Mobile styles */
@media (max-width: 991.98px) {
  .app-header {
    left: 0 !important;
    right: 0 !important;
  }
}

/* Smooth transitions */
.CButton,
.CDropdownToggle {
  transition: all 0.2s ease;
}

.CButton:hover {
  transform: translateY(-1px);
}

.CDropdownToggle:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
