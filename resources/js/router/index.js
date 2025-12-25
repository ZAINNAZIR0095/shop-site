import { createRouter , createWebHistory } from "vue-router";
import dashboard from "../pages/dashboard.vue";
import list from "../pages/products/list.vue";
import create from "../pages/products/create.vue";
import edit from "../pages/products/edit.vue";
import List from "../pages/stocks/list.vue";
import createstock from "../pages/stocks/create.vue";
import Edit from "../pages/stocks/edit.vue";
import Show from "../pages/stocks/show.vue";
import Index from "../pages/reports/index.vue";


const routes = [
    {path: '/' , name:'dashboard' ,  component : dashboard},
    {path: '/products-list' , name:'products' ,  component : list},
  {
    path: '/products-create',
    name: 'products.create',
    component: create
  },
  {
    path: '/products/:id/edit',
    name: 'products.edit',
    component: edit,
    props: true
  },
  {
    path: '/stocks-list',
    name: 'stocks',
    component: List,
    props: true
  },
  {
    path: '/stocks-create',
    name: 'stocks.create',
    component: createstock,
    props: true
  },
  {
    path: '/stocks/:id/edit',
    name: 'stocks.edit',
    component: () => Edit,
},
{
    path: '/stocks-show/:id',
    name: 'stocks.show',
    component: Show,
},
{
    path: '/stocks-report',
    name: 'stocks.report',
    component: Index,
},
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
