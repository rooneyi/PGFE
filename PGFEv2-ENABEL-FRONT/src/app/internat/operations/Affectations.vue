<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import DashPageHeader from '@/components/templates/DashPageHeader.vue'
import { tagInternatNavOperations } from '@/components/templates/internat/tags-links'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import IconifySpinner from '@/components/ui/spinner/IconifySpinner.vue'
import { showCustomToast } from '@/utils/widgets/custom_toast'
import { useGetApi } from '@/composables/useGetApi'
import { usePostApi } from '@/composables/usePostApi'
import { useDeleteApi } from '@/composables/useDeleteApi'
import { API_ROUTES } from '@/utils/constants/api_route'
import api from '@/services/api'

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Internat', href: '/internat' },
    { label: 'Opérations', href: '/internat/operations/affectations' },
    { label: 'Affectations', isActive: true },
  ],
}

const activeTagName = 'affectations'
const query = ref('')
const filterStatut = ref('active')

const { data: rawData, loading: listLoading, fetchData: fetchAffectations } = useGetApi(
  API_ROUTES.GET_INTERNAT_AFFECTATIONS,
)
const { data: studentsRaw, fetchData: fetchStudents } = useGetApi(API_ROUTES.GET_STUDENTS)
const { data: litsRaw, fetchData: fetchLits } = useGetApi(API_ROUTES.GET_INTERNAT_LITS)
const { data: yearsRaw, fetchData: fetchYears } = useGetApi(API_ROUTES.GET_SCHOOL_YEARS)
const { loading: creating, success: createSuccess, postData: createItem, error: createError, errorDetails } =
  usePostApi()
const { deleting, success: deleteSuccess, errorDelete, deleteItem: deleteApi } = useDeleteApi()

const items = computed(() => {
  if (!rawData.value) return []
  if (Array.isArray(rawData.value)) return rawData.value
  return (rawData.value as any).data || []
})

const students = computed(() => {
  const v: any = studentsRaw.value
  if (!v) return []
  if (Array.isArray(v)) return v
  if (Array.isArray(v.data)) return v.data
  return []
})

const litsLibres = computed(() => {
  const v: any = litsRaw.value
  const list = Array.isArray(v) ? v : Array.isArray(v?.data) ? v.data : []
  return list.filter((l: any) => l.status === 'libre')
})

const schoolYears = computed(() => {
  const v: any = yearsRaw.value
  if (!v) return []
  if (Array.isArray(v)) return v
  if (Array.isArray(v.data)) return v.data
  return []
})

const filtered = computed(() => {
  let list = items.value
  if (filterStatut.value) {
    list = list.filter((item: any) => item.statut === filterStatut.value)
  }
  if (!query.value) return list
  const s = query.value.toLowerCase()
  return list.filter((item: any) => {
    const name = `${item.student?.lastname || ''} ${item.student?.firstname || ''}`.toLowerCase()
    const mat = (item.student?.matricule || '').toLowerCase()
    const lit = (item.lit?.code || '').toLowerCase()
    return name.includes(s) || mat.includes(s) || lit.includes(s)
  })
})

const isModalOpen = ref(false)
const formData = ref({
  student_id: '' as string | number,
  lit_id: '' as string | number,
  school_year_id: '' as string | number,
  date_entree: new Date().toISOString().slice(0, 10),
  notes: '',
})

const resetForm = () => {
  formData.value = {
    student_id: '',
    lit_id: '',
    school_year_id: '',
    date_entree: new Date().toISOString().slice(0, 10),
    notes: '',
  }
}

const openCreateModal = () => {
  resetForm()
  fetchLits({ status: 'libre' })
  isModalOpen.value = true
}

const handleSubmit = async () => {
  if (!formData.value.student_id || !formData.value.lit_id || !formData.value.school_year_id) {
    showCustomToast({
      message: 'Élève, lit et année scolaire sont obligatoires',
      type: 'error',
    })
    return
  }

  const payload = {
    student_id: Number(formData.value.student_id),
    lit_id: Number(formData.value.lit_id),
    school_year_id: Number(formData.value.school_year_id),
    date_entree: formData.value.date_entree,
    notes: formData.value.notes || null,
  }

  await createItem(API_ROUTES.CREATE_INTERNAT_AFFECTATION, payload)
  if (createSuccess.value) {
    showCustomToast({ message: 'Affectation créée', type: 'success' })
    isModalOpen.value = false
    fetchAffectations({ statut: filterStatut.value || undefined })
    fetchLits()
  } else {
    const msg =
      (errorDetails.value as any)?.message ||
      createError.value ||
      'Erreur lors de la création'
    showCustomToast({ message: msg, type: 'error' })
  }
}

const checkingOutId = ref<number | null>(null)
const handleCheckout = async (id: number) => {
  checkingOutId.value = id
  try {
    await api.post(API_ROUTES.CHECKOUT_INTERNAT_AFFECTATION(id), {
      date_sortie: new Date().toISOString().slice(0, 10),
    })
    showCustomToast({ message: 'Lit libéré', type: 'success' })
    fetchAffectations({ statut: filterStatut.value || undefined })
    fetchLits()
  } catch (err: any) {
    const msg = err?.response?.data?.message || 'Erreur lors de la libération'
    showCustomToast({ message: msg, type: 'error' })
  } finally {
    checkingOutId.value = null
  }
}

const deletingId = ref<number | null>(null)
const handleDelete = async (id: number) => {
  deletingId.value = id
  await deleteApi(API_ROUTES.DELETE_INTERNAT_AFFECTATION(id))
  deletingId.value = null
  if (deleteSuccess.value) {
    showCustomToast({ message: 'Affectation supprimée', type: 'success' })
    fetchAffectations({ statut: filterStatut.value || undefined })
    fetchLits()
  } else {
    showCustomToast({ message: errorDelete.value || 'Erreur lors de la suppression', type: 'error' })
  }
}

const loadList = () => {
  fetchAffectations(filterStatut.value ? { statut: filterStatut.value } : {})
}

onMounted(() => {
  loadList()
  fetchStudents()
  fetchLits({ status: 'libre' })
  fetchYears()
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/internat/operations/affectations"
    module-name="internat"
  >
    <div class="pb-6 mx-auto w-full max-w-7xl h-full flex flex-col">
      <DashPageHeader
        title="Opérations"
        description="Affectation des élèves aux lits"
        :tags="tagInternatNavOperations"
        :active-tag-name="activeTagName"
      />

      <BoxPanelWrapper class="flex-1 flex flex-col min-h-0">
        <div class="flex sm:items-center gap-3 flex-col sm:flex-row sm:justify-between mb-4">
          <div class="flex flex-1 flex-wrap items-center gap-2">
            <div class="relative w-full max-w-xs">
              <Input
                type="text"
                v-model="query"
                placeholder="Rechercher élève ou lit..."
                class="w-full ps-10 border border-gray-200/40 bg-white h-10 rounded-md"
              />
              <span
                class="absolute left-3 top-1/2 -translate-y-1/2 iconify hugeicons--search-01 text-sm text-foreground-muted/70"
              ></span>
            </div>
            <select
              v-model="filterStatut"
              class="h-10 rounded-md border border-input bg-background px-3 text-sm"
              @change="loadList"
            >
              <option value="active">Actives</option>
              <option value="terminee">Terminées</option>
              <option value="">Toutes</option>
            </select>
          </div>

          <Dialog v-model:open="isModalOpen">
            <DialogTrigger as-child>
              <Button size="md" class="rounded-md" @click="openCreateModal">
                <span class="iconify hugeicons--plus-sign"></span>
                <span class="hidden sm:flex">Nouvelle affectation</span>
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Nouvelle affectation</DialogTitle>
                <DialogDescription>Attribuer un lit libre à un élève interne.</DialogDescription>
              </DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2">
                  <Label>Année scolaire</Label>
                  <select
                    v-model="formData.school_year_id"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                  >
                    <option value="">Sélectionner...</option>
                    <option v-for="y in schoolYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <Label>Élève</Label>
                  <select
                    v-model="formData.student_id"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                  >
                    <option value="">Sélectionner...</option>
                    <option v-for="s in students" :key="s.id" :value="s.id">
                      {{ s.lastname }} {{ s.firstname }}{{ s.matricule ? ` (${s.matricule})` : '' }}
                    </option>
                  </select>
                </div>
                <div class="space-y-2">
                  <Label>Lit libre</Label>
                  <select
                    v-model="formData.lit_id"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                  >
                    <option value="">Sélectionner...</option>
                    <option v-for="l in litsLibres" :key="l.id" :value="l.id">
                      {{
                        l.chambre
                          ? `${l.code} — ${l.chambre.name}${l.chambre.pavillon ? ` (${l.chambre.pavillon.name})` : ''}`
                          : l.code
                      }}
                    </option>
                  </select>
                </div>
                <div class="space-y-2">
                  <Label>Date d'entrée</Label>
                  <Input v-model="formData.date_entree" type="date" />
                </div>
                <div class="space-y-2">
                  <Label>Notes</Label>
                  <Input v-model="formData.notes" placeholder="Optionnel" />
                </div>
              </div>
              <DialogFooter>
                <DialogClose as-child>
                  <Button variant="outline">Annuler</Button>
                </DialogClose>
                <Button @click="handleSubmit" :disabled="creating" class="min-w-[120px]">
                  <IconifySpinner v-if="creating" class="mr-2" />
                  Affecter
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>

        <div
          v-if="listLoading"
          class="flex gap-2 items-center justify-center py-10 bg-white rounded-md text-gray-500"
        >
          <span class="iconify animate-spin hugeicons--loading-03 text-2xl"></span>
          <span>Chargement...</span>
        </div>

        <div v-else-if="filtered.length" class="mt-4 rounded-md overflow-hidden bg-white">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Élève</TableHead>
                <TableHead>Lit</TableHead>
                <TableHead>Chambre</TableHead>
                <TableHead>Entrée</TableHead>
                <TableHead>Statut</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in filtered" :key="item.id">
                <TableCell>
                  <div class="font-medium">
                    {{ item.student?.lastname }} {{ item.student?.firstname }}
                  </div>
                  <div class="text-xs text-foreground-muted">{{ item.student?.matricule }}</div>
                </TableCell>
                <TableCell>{{ item.lit?.code }}</TableCell>
                <TableCell>
                  {{ item.lit?.chambre?.name || '—' }}
                  <span v-if="item.lit?.chambre?.pavillon" class="text-foreground-muted text-xs">
                    ({{ item.lit.chambre.pavillon.name }})
                  </span>
                </TableCell>
                <TableCell>{{ item.date_entree }}</TableCell>
                <TableCell>
                  <Badge :variant="item.statut === 'active' ? 'default' : 'secondary'">
                    {{ item.statut === 'active' ? 'Active' : 'Terminée' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right space-x-2">
                  <Button
                    v-if="item.statut === 'active'"
                    variant="outline"
                    size="sm"
                    :disabled="checkingOutId === item.id"
                    @click="handleCheckout(item.id)"
                  >
                    Libérer
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    :disabled="deleting && deletingId === item.id"
                    @click="handleDelete(item.id)"
                  >
                    Supprimer
                  </Button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div v-else class="py-12 text-center text-foreground-muted bg-white rounded-md">
          Aucune affectation trouvée
        </div>
      </BoxPanelWrapper>
    </div>
  </DashLayout>
</template>
