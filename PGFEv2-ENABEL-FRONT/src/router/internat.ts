import type { RouteRecordRaw } from 'vue-router'

const internatRoutes: RouteRecordRaw[] = [
  {
    meta: { permission: 'internat.full' },
    path: '/internat',
    redirect: '/internat/dashboard',
  },
  {
    meta: { permission: 'internat.full' },
    path: '/internat/dashboard',
    name: 'internat-dashboard',
    component: () => import('@/app/internat/Dashboard.vue'),
  },
  {
    meta: { permission: 'internat.full' },
    path: '/internat/prealables/pavillons',
    name: 'internat-prealables-pavillons',
    component: () => import('@/app/internat/prealables/Pavillons.vue'),
  },
  {
    meta: { permission: 'internat.full' },
    path: '/internat/prealables/chambres',
    name: 'internat-prealables-chambres',
    component: () => import('@/app/internat/prealables/Chambres.vue'),
  },
  {
    meta: { permission: 'internat.full' },
    path: '/internat/prealables/lits',
    name: 'internat-prealables-lits',
    component: () => import('@/app/internat/prealables/Lits.vue'),
  },
  {
    meta: { permission: 'internat.full' },
    path: '/internat/operations/affectations',
    name: 'internat-operations-affectations',
    component: () => import('@/app/internat/operations/Affectations.vue'),
  },
]

export { internatRoutes }
export default internatRoutes
