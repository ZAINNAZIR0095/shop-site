<!-- resources/js/components/Navbar.vue -->
<template>
  <header>
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
            ☰
          </CButton>

          <!-- Desktop collapse toggle (always visible) -->
          <CButton
            color="light"
            class="d-none d-lg-block"
            @click="$emit('toggle-collapse')"
          >
            <CIcon :icon="sidebarCollapsed ? cilChevronRightDouble : cilChevronLeftDouble" />
          </CButton>
        </div>

        <!-- Brand -->
        <CNavbarBrand href="#" class="d-flex align-items-center">
          <CIcon :icon="cilInventory" height="32" class="me-2" />
          Inventory System
        </CNavbarBrand>

        <div class="flex-grow-1"></div>

        <!-- User Dropdown -->
        <CNavbarNav>
          <CDropdown variant="nav-item">
            <CDropdownToggle color="secondary" class="py-0 px-2 d-flex align-items-center">
              <CAvatar :text="userInitials" size="md" class="me-2" />
              {{ authStore.currentUser?.name || 'User' }}
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

const router = useRouter()
const authStore = useAuthStore()

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
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1050;
  height: 56px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  transition: left 0.3s ease;
}

@media (min-width: 992px) {
  .app-header.sidebar-collapsed {
    left: 70px !important;
  }
}
</style>
