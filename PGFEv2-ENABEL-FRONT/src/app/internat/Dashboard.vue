<script setup lang="ts">
import { computed, onMounted } from 'vue'
import PageAnimationWrapper from '@/components/atoms/CAnimationWrapper.vue'
import CardDashStat from '@/components/molecules/CardDashStat.vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { API_ROUTES } from '@/utils/constants/api_route'
import { useGetApi } from '@/composables/useGetApi'

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Internat', href: '/internat' },
    { label: 'Tableau de bord', isActive: true },
  ],
}

interface InternatDashboardData {
  total_pavillons: number
  total_chambres: number
  total_lits: number
  lits_libres: number
  lits_occupes: number
  lits_hors_service: number
  internes_actifs: number
  taux_occupation: number
  affectations_recentes: any[]
}

const { data: rawData, loading, fetchData } = useGetApi<InternatDashboardData>(
  API_ROUTES.GET_INTERNAT_DASHBOARD,
)

const dashboard = computed(() => {
  const v: any = rawData.value
  if (!v) return null
  return (v.data ?? v) as InternatDashboardData
})

onMounted(() => {
  fetchData()
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/internat/dashboard"
    module-name="internat"
  >
    <PageAnimationWrapper class="mx-auto w-full max-w-7xl px-4 sm:px-8 md:px-10 lg:px-12">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-fg-title">Tableau de bord Internat</h1>
        <p class="text-sm text-foreground-muted mt-1">Occupation des lits et internes actifs</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16 text-gray-500 gap-2">
        <span class="iconify animate-spin hugeicons--loading-03 text-2xl"></span>
        <span>Chargement...</span>
      </div>

      <template v-else-if="dashboard">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <CardDashStat
            icon="hugeicons--building-03"
            title="Pavillons"
            :value="dashboard.total_pavillons"
            description="Bâtiments d'hébergement"
            color="#2563eb"
          />
          <CardDashStat
            icon="hugeicons--door-01"
            title="Chambres"
            :value="dashboard.total_chambres"
            description="Chambres enregistrées"
            color="#0891b2"
          />
          <CardDashStat
            icon="hugeicons--bed-single-01"
            title="Lits libres"
            :value="dashboard.lits_libres"
            :description="`${dashboard.total_lits} lits au total`"
            color="#16a34a"
          />
          <CardDashStat
            icon="hugeicons--user-group"
            title="Internes actifs"
            :value="dashboard.internes_actifs"
            :description="`Taux d'occupation ${dashboard.taux_occupation}%`"
            color="#ca8a04"
          />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
          <div class="bg-white rounded-lg border border-border/30 p-4">
            <p class="text-sm text-foreground-muted">Lits occupés</p>
            <p class="text-2xl font-semibold text-fg-title">{{ dashboard.lits_occupes }}</p>
          </div>
          <div class="bg-white rounded-lg border border-border/30 p-4">
            <p class="text-sm text-foreground-muted">Hors service</p>
            <p class="text-2xl font-semibold text-fg-title">{{ dashboard.lits_hors_service }}</p>
          </div>
          <div class="bg-white rounded-lg border border-border/30 p-4">
            <p class="text-sm text-foreground-muted">Taux d'occupation</p>
            <p class="text-2xl font-semibold text-fg-title">{{ dashboard.taux_occupation }}%</p>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-border/30 overflow-hidden">
          <div class="px-4 py-3 border-b border-border/30">
            <h2 class="font-medium text-fg-title">Affectations actives récentes</h2>
          </div>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Élève</TableHead>
                <TableHead>Matricule</TableHead>
                <TableHead>Lit</TableHead>
                <TableHead>Chambre</TableHead>
                <TableHead>Entrée</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="!dashboard.affectations_recentes?.length">
                <TableCell colspan="5" class="text-center text-foreground-muted py-8">
                  Aucune affectation active
                </TableCell>
              </TableRow>
              <TableRow v-for="item in dashboard.affectations_recentes" :key="item.id">
                <TableCell>
                  {{ item.student?.lastname }} {{ item.student?.firstname }}
                </TableCell>
                <TableCell>
                  <Badge variant="secondary">{{ item.student?.matricule || '—' }}</Badge>
                </TableCell>
                <TableCell>{{ item.lit?.code }}</TableCell>
                <TableCell>{{ item.lit?.chambre?.name || '—' }}</TableCell>
                <TableCell>{{ item.date_entree }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </template>
    </PageAnimationWrapper>
  </DashLayout>
</template>
