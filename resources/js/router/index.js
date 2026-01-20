import { createRouter, createWebHistory } from "vue-router";

// Layouts
import DefaultLayout from "../layouts/DefaultLayout.vue";
import AuthLayout from "../layouts/AuthLayout.vue";
// Pages
import dashboard from "../pages/dashboard.vue";
import list from "../pages/products/list.vue";
import create from "../pages/products/create.vue";
import edit from "../pages/products/edit.vue";
import List from "../pages/stocks/list.vue";
import createstock from "../pages/stocks/create.vue";
import Edit from "../pages/stocks/edit.vue";
import Show from "../pages/stocks/show.vue";
import Index from "../pages/reports/index.vue";
import transactionReport from "../pages/reports/transaction-report.vue";
import login from "../pages/auth/login.vue";

import { useAuthStore } from "../stores/authStore";
import customersList from "../pages/customers/list.vue";
import suppliersList from "../pages/suppliers/list.vue"
import SupplierTransactions from "../pages/suppliers/transaction.vue"

const routes = [
  // Auth Routes
  {
    path: "/login",
    component: AuthLayout,
    meta: { requiresGuest: true },
    children: [
      {
        path: "",
        name: "login",
        component: login,
      },
    ],
  },

  // Protected Routes with Default Layout (Sidebar + Navbar)
  {
    path: "/",
    component: DefaultLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "dashboard",
        component: dashboard,
      },
      {
        path: "products-list",
        name: "products",
        component: list,
      },
      {
        path: "customers-list",
        name: "customers",
        component: customersList,
      },
      {
        path: "suppliers-list",
        name: "suppliers",
        component: suppliersList,
      },
        {
    path: '/suppliers/:id/transactions',
    name: 'suppliers.transactions',
    component: SupplierTransactions,
  },
      {
        path: "products-create",
        name: "products.create",
        component: create,
      },
      {
        path: "products/:id/edit",
        name: "products.edit",
        component: edit,
        props: true,
      },
      {
        path: "stocks-list",
        name: "stocks",
        component: List,
      },
      {
        path: "stocks-create",
        name: "stocks.create",
        component: createstock,
      },
      {
        path: "stocks/:id/edit",
        name: "stocks.edit",
        component: Edit,
        props: true,
      },
      {
        path: "stocks-show/:id",
        name: "stocks.show",
        component: Show,
        props: true,
      },
      {
        path: "stocks-report",
        name: "stocks.report",
        component: Index,
      },
      {
        path: "transaction-report",
        name: "stocks.transaction",
        component: transactionReport,
      },
    ],
  },

  // Fallback
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation Guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Routes requiring authentication
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      const isAuthenticated = await authStore.checkAuth();
      if (!isAuthenticated) {
        return next("/login");
      }
    }
  }

  // Routes requiring guest (not logged in)
  if (to.matched.some((record) => record.meta.requiresGuest)) {
    if (authStore.isAuthenticated) {
      return next("/");
    }
  }

    const auth = useAuthStore()

  if (auth.user?.email === 'staff@ims.com') {
    // Only allow stock-related routes
    const allowedRoutes = ['stocks', 'stocks.create', 'stocks.edit', 'stocks.show']

    if (!allowedRoutes.includes(to.name)) {
      // Redirect to stocks list if user tries to access other pages
      return next({ name: 'stocks' })
    }
  }

  next();
});

export default router;
