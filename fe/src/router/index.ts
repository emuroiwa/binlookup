import { createRouter, createWebHistory } from 'vue-router'
import BinImportsView from '../views/BinImportsView.vue'
import BinDataView from '../views/BinDataView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'bin-imports',
      component: BinImportsView,
    },
    {
      path: '/bin-imports',
      name: 'bin-imports-alt',
      component: BinImportsView,
    },
    {
      path: '/bin-data',
      name: 'bin-data',
      component: BinDataView,
    },
  ],
})

export default router
