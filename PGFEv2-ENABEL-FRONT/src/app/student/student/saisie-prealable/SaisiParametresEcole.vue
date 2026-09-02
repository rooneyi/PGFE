<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import LayoutSaisieOperation from '@/components/templates/LayoutSaisieOperation.vue'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import IconifySpinner from '@/components/ui/spinner/IconifySpinner.vue'
import { API_ROUTES } from '@/utils/constants/api_route'
import { useGetApi } from '@/composables/useGetApi'
import { usePostApi } from '@/composables/usePostApi'
import { showCustomToast } from '@/utils/widgets/custom_toast'

interface TrackStatus {
  key: string
  label: string
  code?: string
  cycle?: string
  levels: string[]
  installed: boolean
}

interface CycleRow {
  id: number
  name: string
  filiaire?: { name?: string } | null
  academic_levels?: { id: number; name: string }[]
}

interface StructurePayload {
  tracks?: TrackStatus[]
  secondary_levels?: string[]
  sections?: { id: number; name: string; code?: string | null }[]
  cycles?: CycleRow[]
}

const fallbackTracks: TrackStatus[] = [
  {
    key: 'maternelle',
    label: 'Maternelle',
    levels: ['1ère maternelle', '2ème maternelle', '3ème maternelle'],
    installed: false,
  },
  {
    key: 'primaire',
    label: 'Primaire',
    levels: [
      '1ère primaire',
      '2ème primaire',
      '3ème primaire',
      '4ème primaire',
      '5ème primaire',
      '6ème primaire',
    ],
    installed: false,
  },
  {
    key: 'base_7_8',
    label: 'Enseignement de base',
    levels: ['7ème de base', '8ème de base'],
    installed: false,
  },
]

const selected = ref<Record<string, boolean>>({
  maternelle: true,
  primaire: true,
  base_7_8: true,
})

const {
  data: structure,
  loading,
  fetchData,
} = useGetApi<StructurePayload>(API_ROUTES.EDUCATION_TRACKS)
const { postData, loading: installing, success, error } = usePostApi<StructurePayload>()

const tracks = computed<TrackStatus[]>(() =>
  structure.value?.tracks?.length ? structure.value.tracks : fallbackTracks,
)
const secondaryLevels = computed(
  () => structure.value?.secondary_levels ?? ['1ère', '2ème', '3ème', '4ème'],
)
const cycles = computed<CycleRow[]>(() => structure.value?.cycles ?? [])

onMounted(async () => {
  await fetchData()
  for (const track of tracks.value) {
    selected.value[track.key] = true
  }
})

const chosenKeys = computed(() =>
  Object.entries(selected.value)
    .filter(([, on]) => on)
    .map(([key]) => key),
)

async function installTracks() {
  if (chosenKeys.value.length === 0) {
    showCustomToast({ message: 'Choisissez au moins un cycle (maternelle, primaire ou 7e-8e).', type: 'error' })
    return
  }
  await postData(API_ROUTES.EDUCATION_TRACKS, {
    tracks: chosenKeys.value,
    trim_secondary: true,
  })
  if (success.value) {
    showCustomToast({
      message: 'Structure installée. Les humanités / technique vont de la 1ère à la 4ème.',
      type: 'success',
    })
    await fetchData()
  } else {
    showCustomToast({ message: error.value || 'Installation impossible', type: 'error' })
  }
}
</script>

<template>
  <LayoutSaisieOperation
    active-tag-name="structure"
    group="saisie"
    :breadcrumb="[
      { label: 'Élèves', href: '/apprenants' },
      { label: 'Saisie Préalable', href: '/apprenants/saisie-prealable' },
      { label: 'Structure scolaire', href: '/apprenants/saisie-prealable/structure' },
    ]"
  >
    <BoxPanelWrapper>
      <div v-if="loading" class="flex items-center gap-2 py-10 justify-center text-muted-foreground">
        <IconifySpinner /> Chargement de la structure…
      </div>

      <div v-else class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Cycles d’enseignement de base</CardTitle>
            <CardDescription>
              L’école peut gérer la maternelle, le primaire et la 7ème / 8ème de base. Les
              humanités et sections techniques vont de la <strong>1ère à la 4ème</strong>
              (plus de 5ème-6ème).
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <label
              v-for="track in tracks"
              :key="track.key"
              class="flex items-start gap-3 rounded-xl border p-4 cursor-pointer"
              :class="selected[track.key] ? 'border-primary bg-primary/5' : 'border-border bg-white'"
            >
              <Checkbox
                :checked="Boolean(selected[track.key])"
                @update:checked="(v) => (selected[track.key] = Boolean(v))"
                class="mt-0.5"
              />
              <span>
                <span class="font-semibold">{{ track.label }}</span>
                <span
                  v-if="track.installed"
                  class="ml-2 text-xs font-medium text-emerald-700"
                >
                  déjà installé
                </span>
                <span class="block text-sm text-muted-foreground">
                  {{ track.levels.join(' · ') }}
                </span>
              </span>
            </label>

            <div class="rounded-xl border border-dashed px-4 py-3 text-sm text-muted-foreground">
              Humanités / technique : {{ secondaryLevels.join(' · ') }}
            </div>

            <div class="flex justify-end">
              <Button :disabled="installing" @click="installTracks">
                <IconifySpinner v-if="installing" class="mr-2" />
                Installer la structure
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card v-if="cycles.length">
          <CardHeader>
            <CardTitle>Cycles déjà en place</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div v-for="cycle in cycles" :key="cycle.id" class="text-sm">
              <p class="font-medium">
                {{ cycle.name }}
                <span class="text-muted-foreground">— {{ cycle.filiaire?.name || 'sans section' }}</span>
              </p>
              <p class="text-muted-foreground">
                {{ (cycle.academic_levels || []).map((l) => l.name).join(' · ') || 'Aucun niveau' }}
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </BoxPanelWrapper>
  </LayoutSaisieOperation>
</template>
