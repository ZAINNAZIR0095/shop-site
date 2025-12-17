import { createRouter , createWebHistory } from "vue-router";
import dashboard from "../pages/dashboard.vue";
import list from "../pages/products/list.vue";
import create from "../pages/products/create.vue";
import edit from "../pages/products/edit.vue";


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
  }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

export default router
