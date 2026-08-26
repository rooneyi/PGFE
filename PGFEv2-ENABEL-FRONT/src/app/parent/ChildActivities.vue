<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageAnimationWrapper from '@/components/atoms/CAnimationWrapper.vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { API_ROUTES } from '@/utils/constants/api_route'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const studentId = computed(() => String(route.params.studentId || ''))
const activeTab = ref<'activities' | 'presences' | 'bulletin'>('activities')

const breadcrumbItems = computed(() => ({
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Espace Parent', href: '/espace-parent' },
    { label: 'Activités enfant', isActive: true },
  ],
}))

const childState = ref<any>(null)
const activitiesState = ref<any[]>([])
const presencesState = ref<any[]>([])
const bulletinState = ref<any>(null)
const loading = ref(false)
const tabLoading = ref(false)
const errorMsg = ref<string | null>(null)

const statusLabel = (status: string) => {
  switch (status) {
    case 'present':
      return 'Présent'
    case 'sick':
      return 'Malade'
    case 'absent_justified':
      return 'Absent justifié'
    default:
      return 'Absent'
  }
}

const loadChildInfo = async () => {
  loading.value = true
  errorMsg.value = null
  try {
    const { data } = await api.get(
      API_ROUTES.PARENT_CHILD.replace(':studentId', studentId.value),
    )
    childState.value = data?.data ?? data
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Impossible de charger cet enfant'
  } finally {
    loading.value = false
  }
}

const loadTabData = async () => {
  if (!studentId.value) return
  tabLoading.value = true
  errorMsg.value = null
  try {
    if (activeTab.value === 'activities') {
      const { data } = await api.get(
        API_ROUTES.PARENT_CHILD_ACTIVITIES.replace(':studentId', studentId.value),
      )
      activitiesState.value = Array.isArray(data) ? data : (data?.data ?? [])
    } else if (activeTab.value === 'presences') {
      const { data } = await api.get(
        API_ROUTES.PARENT_CHILD_PRESENCES.replace(':studentId', studentId.value),
      )
      presencesState.value = Array.isArray(data) ? data : (data?.data ?? [])
    } else {
      const { data } = await api.get(
        API_ROUTES.PARENT_CHILD_BULLETIN.replace(':studentId', studentId.value),
      )
      bulletinState.value = data?.data ?? data
    }
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || 'Erreur de chargement'
  } finally {
    tabLoading.value = false
  }
}

watch(activeTab, () => {
  loadTabData()
})

onMounted(async () => {
  await loadChildInfo()
  await loadTabData()
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/espace-parent"
    module-name="parent"
  >
    <PageAnimationWrapper class="mx-auto w-full max-w-7xl px-4 sm:px-8 md:px-10 lg:px-12">
      <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
          <Button variant="ghost" class="mb-2 px-0" @click="router.push('/espace-parent')">
            ← Retour aux enfants
          </Button>
          <h1 class="text-2xl font-semibold text-fg-title">
            <template v-if="childState">
              {{ childState.name }} {{ childState.firstname }} {{ childState.lastname }}
            </template>
            <template v-else>Détail enfant</template>
          </h1>
          <p v-if="childState" class="text-sm text-foreground-muted mt-1">
            {{ childState.classroom?.name || 'Classe non définie' }}
            <span v-if="childState.school_year?.name"> · {{ childState.school_year.name }}</span>
          </p>
        </div>
      </div>

      <div
        v-if="errorMsg"
        class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-red-700"
      >
        {{ errorMsg }}
      </div>

      <div class="flex flex-wrap gap-2 mb-6">
        <Button
          :variant="activeTab === 'activities' ? 'default' : 'outline'"
          @click="activeTab = 'activities'"
        >
          Activités
        </Button>
        <Button
          :variant="activeTab === 'presences' ? 'default' : 'outline'"
          @click="activeTab = 'presences'"
        >
          Présences
        </Button>
        <Button
          :variant="activeTab === 'bulletin' ? 'default' : 'outline'"
          @click="activeTab = 'bulletin'"
        >
          Bulletin
        </Button>
      </div>

      <div v-if="loading" class="py-10 text-center text-gray-500">Chargement...</div>

      <template v-else>
        <div v-if="activeTab === 'activities'" class="rounded-md bg-white border overflow-hidden">
          <div v-if="tabLoading" class="p-8 text-center text-gray-500">Chargement...</div>
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Activité</TableHead>
                <TableHead>Lieu</TableHead>
                <TableHead>Début</TableHead>
                <TableHead>Fin</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="activitiesState.length === 0">
                <TableCell :colspan="4" class="text-center py-8 text-gray-500">
                  Aucune activité pour la classe de cet enfant
                </TableCell>
              </TableRow>
              <TableRow v-for="item in activitiesState" :key="item.id">
                <TableCell>
                  <div class="font-medium">{{ item.activity?.label || '—' }}</div>
                  <div class="text-xs text-foreground-muted">
                    {{ item.activity?.description || '' }}
                  </div>
                </TableCell>
                <TableCell>{{ item.activity?.place || '—' }}</TableCell>
                <TableCell>{{ item.activity?.start_date || '—' }}</TableCell>
                <TableCell>{{ item.activity?.end_date || '—' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div
          v-else-if="activeTab === 'presences'"
          class="rounded-md bg-white border overflow-hidden"
        >
          <div v-if="tabLoading" class="p-8 text-center text-gray-500">Chargement...</div>
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Classe</TableHead>
                <TableHead>Statut</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="presencesState.length === 0">
                <TableCell :colspan="3" class="text-center py-8 text-gray-500">
                  Aucune présence enregistrée
                </TableCell>
              </TableRow>
              <TableRow v-for="item in presencesState" :key="item.id">
                <TableCell>{{ item.date || '—' }}</TableCell>
                <TableCell>{{ item.classroom?.name || '—' }}</TableCell>
                <TableCell>{{ statusLabel(item.status) }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div v-else class="rounded-md bg-white border p-6">
          <div v-if="tabLoading" class="py-8 text-center text-gray-500">Chargement...</div>
          <div v-else-if="!bulletinState" class="py-8 text-center text-gray-500">
            Bulletin indisponible pour le moment
          </div>
          <div v-else class="space-y-4">
            <div>
              <h2 class="text-lg font-medium">Bulletin scolaire</h2>
              <p class="text-sm text-foreground-muted">
                {{
                  bulletinState.registration?.classroom?.name ||
                  childState?.classroom?.name ||
                  ''
                }}
                <span v-if="bulletinState.registration?.school_year?.name">
                  · {{ bulletinState.registration.school_year.name }}
                </span>
              </p>
            </div>
            <div v-if="bulletinState.summary" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="rounded border p-3">
                <div class="text-xs text-foreground-muted">Moyenne</div>
                <div class="text-lg font-semibold">
                  {{ bulletinState.summary.average ?? bulletinState.summary.moyenne ?? '—' }}
                </div>
              </div>
              <div class="rounded border p-3">
                <div class="text-xs text-foreground-muted">Pourcentage</div>
                <div class="text-lg font-semibold">
                  {{
                    bulletinState.summary.percentage ?? bulletinState.summary.pourcentage ?? '—'
                  }}
                </div>
              </div>
            </div>
            <Table v-if="Array.isArray(bulletinState.grades) && bulletinState.grades.length">
              <TableHeader>
                <TableRow>
                  <TableHead>Cours</TableHead>
                  <TableHead>Note</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(grade, idx) in bulletinState.grades" :key="idx">
                  <TableCell>
                    {{ grade.course?.label || grade.course?.name || grade.label || '—' }}
                  </TableCell>
                  <TableCell>{{ grade.score ?? grade.note ?? grade.average ?? '—' }}</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </div>
      </template>
    </PageAnimationWrapper>
  </DashLayout>
</template>
