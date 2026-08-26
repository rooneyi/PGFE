import type { RouteRecordRaw } from 'vue-router'

const parentRoutes: RouteRecordRaw[] = [
  {
    meta: { permission: 'parent.portal' },
    path: '/espace-parent',
    name: 'parent-home',
    component: () => import('@/app/parent/ParentHome.vue'),
  },
  {
    meta: { permission: 'parent.portal' },
    path: '/espace-parent/enfants/:studentId',
    name: 'parent-child',
    component: () => import('@/app/parent/ChildActivities.vue'),
  },
  {
    meta: { permission: 'parent.forum.view' },
    path: '/espace-parent/forum',
    name: 'parent-forum',
    component: () => import('@/app/parent/ParentForum.vue'),
  },
]

export { parentRoutes }
export default parentRoutes
