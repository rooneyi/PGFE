<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageAnimationWrapper from '@/components/atoms/CAnimationWrapper.vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import { Button } from '@/components/ui/button'
import { API_ROUTES } from '@/utils/constants/api_route'
import { useGetApi } from '@/composables/useGetApi'

const router = useRouter()

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Espace Parent', isActive: true },
  ],
}

const { data: rawData, loading, error, fetchData } = useGetApi<any[]>(API_ROUTES.PARENT_CHILDREN)

const children = computed(() => {
  const v: any = rawData.value
  if (!v) return []
  if (Array.isArray(v)) return v
  return v.data ?? []
})

const openChild = (id: number) => {
  router.push(`/espace-parent/enfants/${id}`)
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/espace-parent"
    module-name="parent"
  >
    <PageAnimationWrapper class="mx-auto w-full max-w-7xl px-4 sm:px-8 md:px-10 lg:px-12">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-fg-title">Mes enfants</h1>
        <p class="text-sm text-foreground-muted mt-1">
          Consultez les activités, présences et bulletins de vos enfants
        </p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16 text-gray-500 gap-2">
        <span class="iconify animate-spin hugeicons--loading-03 text-2xl"></span>
        <span>Chargement...</span>
      </div>

      <div v-else-if="error" class="rounded-md bg-white border p-6 text-red-600">
        {{ error }}
      </div>

      <div
        v-else-if="children.length === 0"
        class="rounded-md bg-white border p-10 text-center text-gray-500"
      >
        Aucun enfant lié à votre compte pour le moment.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="child in children"
          :key="child.id"
          class="rounded-md border bg-white p-5 flex flex-col gap-3"
        >
          <div>
            <h2 class="text-lg font-medium text-fg-title">
              {{ child.name }} {{ child.firstname }} {{ child.lastname }}
            </h2>
            <p class="text-sm text-foreground-muted mt-1">
              {{ child.classroom?.name || 'Classe non définie' }}
              <span v-if="child.school_year?.name"> · {{ child.school_year.name }}</span>
            </p>
            <p v-if="child.matricule" class="text-xs text-foreground-muted mt-1">
              Matricule: {{ child.matricule }}
            </p>
          </div>
          <Button class="w-full mt-auto" @click="openChild(child.id)">
            Voir les activités
          </Button>
        </div>
      </div>
    </PageAnimationWrapper>
  </DashLayout>
</template>
