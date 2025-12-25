<template>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <CSidebar  class="sidebar-fixed">
            <CSidebarHeader class="border-bottom">
                <CSidebarBrand>IMS-SZB</CSidebarBrand>
            </CSidebarHeader>
            <CSidebarNav>

                <!-- Dashboard Link -->
                <CNavItem>
                    <router-link to="/" class="nav-link">
                        <CIcon custom-class-name="nav-icon" :name="cilSpeedometer" />
                        Dashboard
                    </router-link>
                </CNavItem>

                <!-- Products Link -->
                <CNavItem>
                    <router-link to="/products-list" class="nav-link">
                        <CIcon custom-class-name="nav-icon" :name="cilSpeedometer" />
                        Products
                    </router-link>
                </CNavItem>
                <!-- Products Link -->
                <CNavItem>
                    <router-link to="/stocks-list" class="nav-link">
                        <CIcon custom-class-name="nav-icon" :name="cilSpeedometer" />
                        Stock List
                    </router-link>
                </CNavItem>
                <!-- Products Link -->
                <CNavItem>
                    <router-link to="/stocks-report" class="nav-link">
                        <CIcon custom-class-name="nav-icon" :name="cilSpeedometer" />
                        Stock report
                    </router-link>
                </CNavItem>

            </CSidebarNav>
        </CSidebar>

        <!-- Main Content Area with sidebar offset -->
        <div class="main-content">
            <!-- Page Content -->
            <div class="body flex-grow-1">
                <CContainer fluid>
                    <router-view />
                </CContainer>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import {
    CSidebar, CSidebarHeader, CSidebarBrand, CSidebarNav,
    CNavTitle, CNavItem, CContainer
} from '@coreui/vue'

import {
    cilSpeedometer
} from '@coreui/icons'

const router = useRouter()
</script>

<style scoped>
.wrapper {
    min-height: 100vh;
    overflow-x: hidden;
}

/* Sidebar styling */
.sidebar-fixed {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

/* Main content area - adjust for sidebar */
.main-content {
    margin-left: 256px; /* Default sidebar width */
    width: calc(100% - 256px);
    min-height: 100vh;
    transition: margin-left 0.3s;
}

/* When sidebar is collapsed */
.c-sidebar:not(.c-sidebar-unfoldable) {
    width: 56px; /* Collapsed width */
}

.c-sidebar:not(.c-sidebar-unfoldable) + .main-content {
    margin-left: 56px;
    width: calc(100% - 56px);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .sidebar-fixed {
        position: fixed;
        z-index: 1035;
    }

    .main-content {
        margin-left: 0;
        width: 100%;
    }

    /* Show sidebar overlay on mobile */
    .c-sidebar.c-show:not(.c-sidebar-unfoldable) ~ .main-content {
        margin-left: 0;
    }
}

/* Nav link styling */
.nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
}

.nav-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: #000;
}

.nav-link.router-link-exact-active {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    border-left: 3px solid #0d6efd;
}

.nav-icon {
    margin-right: 0.5rem;
    width: 16px;
    height: 16px;
}

/* Body content styling */
.body {
    padding: 1.5rem;
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px); /* Adjust if you have header/footer */
}
</style>
