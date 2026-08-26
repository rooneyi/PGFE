<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import DashPageHeader from '@/components/templates/DashPageHeader.vue'
import { tagInternatNavPrealables } from '@/components/templates/internat/tags-links'
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
import { usePutApi } from '@/composables/usePutApi'
import { useDeleteApi } from '@/composables/useDeleteApi'
import { API_ROUTES } from '@/utils/constants/api_route'

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Internat', href: '/internat' },
    { label: 'Préalables', href: '/internat/prealables/pavillons' },
    { label: 'Lits', isActive: true },
  ],
}

const activeTagName = 'lits'
const query = ref('')

const { data: rawData, loading: listLoading, fetchData: fetchLits } = useGetApi(
  API_ROUTES.GET_INTERNAT_LITS,
)
const { data: chambresRaw, fetchData: fetchChambres } = useGetApi(API_ROUTES.GET_INTERNAT_CHAMBRES)
const { loading: creating, success: createSuccess, postData: createItem, error: createError } =
  usePostApi()
const { loading: updating, success: updateSuccess, putData: updateItem, error: updateError } =
  usePutApi()
const { deleting, success: deleteSuccess, errorDelete, deleteItem: deleteApi } = useDeleteApi()

const items = computed(() => {
  if (!rawData.value) return []
  if (Array.isArray(rawData.value)) return rawData.value
  return (rawData.value as any).data || []
})

const chambres = computed(() => {
  if (!chambresRaw.value) return []
  if (Array.isArray(chambresRaw.value)) return chambresRaw.value
  return (chambresRaw.value as any).data || []
})

const filtered = computed(() => {
  if (!query.value) return items.value
  const s = query.value.toLowerCase()
  return items.value.filter(
    (item: any) =>
      item.code?.toLowerCase().includes(s) ||
      item.chambre?.name?.toLowerCase().includes(s) ||
      item.chambre?.pavillon?.name?.toLowerCase().includes(s),
  )
})

const statusLabel = (s: string) =>
  ({ libre: 'Libre', occupe: 'Occupé', hors_service: 'Hors service' }[s] || s)

const statusVariant = (s: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
  if (s === 'libre') return 'default'
  if (s === 'occupe') return 'secondary'
  return 'outline'
}

const isModalOpen = ref(false)
const isEditing = ref(false)
const currentId = ref<number | null>(null)
const formData = ref({
  code: '',
  chambre_id: '' as string | number,
  status: 'libre',
})

const resetForm = () => {
  formData.value = { code: '', chambre_id: '', status: 'libre' }
  isEditing.value = false
  currentId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (item: any) => {
  formData.value = {
    code: item.code || '',
    chambre_id: item.chambre_id,
    status: item.status || 'libre',
  }
  currentId.value = item.id
  isEditing.value = true
  isModalOpen.value = true
}

const handleSubmit = async () => {
  if (!formData.value.code.trim() || !formData.value.chambre_id) {
    showCustomToast({ message: 'Code et chambre sont obligatoires', type: 'error' })
    return
  }

  const payload = {
    code: formData.value.code,
    chambre_id: Number(formData.value.chambre_id),
    status: formData.value.status,
  }

  if (isEditing.value && currentId.value) {
    await updateItem(API_ROUTES.UPDATE_INTERNAT_LIT(currentId.value), payload)
    if (updateSuccess.value) {
      showCustomToast({ message: 'Lit mis à jour', type: 'success' })
      isModalOpen.value = false
      fetchLits()
    } else {
      showCustomToast({ message: updateError.value || 'Erreur lors de la mise à jour', type: 'error' })
    }
  } else {
    await createItem(API_ROUTES.CREATE_INTERNAT_LIT, payload)
    if (createSuccess.value) {
      showCustomToast({ message: 'Lit créé', type: 'success' })
      isModalOpen.value = false
      fetchLits()
    } else {
      showCustomToast({ message: createError.value || 'Erreur lors de la création', type: 'error' })
    }
  }
}

const deletingId = ref<number | null>(null)
const handleDelete = async (id: number) => {
  deletingId.value = id
  await deleteApi(API_ROUTES.DELETE_INTERNAT_LIT(id))
  deletingId.value = null
  if (deleteSuccess.value) {
    showCustomToast({ message: 'Lit supprimé', type: 'success' })
    fetchLits()
  } else {
    showCustomToast({ message: errorDelete.value || 'Erreur lors de la suppression', type: 'error' })
  }
}

onMounted(() => {
  fetchLits()
  fetchChambres()
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/internat/prealables/pavillons"
    module-name="internat"
  >
    <div class="pb-6 mx-auto w-full max-w-7xl h-full flex flex-col">
      <DashPageHeader
        title="Préalables"
        description="Gestion des lits"
        :tags="tagInternatNavPrealables"
        :active-tag-name="activeTagName"
      />

      <BoxPanelWrapper class="flex-1 flex flex-col min-h-0">
        <div class="flex sm:items-center gap-3 flex-col sm:flex-row sm:justify-between mb-4">
          <div class="relative w-full max-w-xs">
            <Input
              type="text"
              v-model="query"
              placeholder="Rechercher..."
              class="w-full ps-10 border border-gray-200/40 bg-white h-10 rounded-md"
            />
            <span
              class="absolute left-3 top-1/2 -translate-y-1/2 iconify hugeicons--search-01 text-sm text-foreground-muted/70"
            ></span>
          </div>

          <Dialog v-model:open="isModalOpen">
            <DialogTrigger as-child>
              <Button size="md" class="rounded-md" @click="openCreateModal">
                <span class="iconify hugeicons--plus-sign"></span>
                <span class="hidden sm:flex">Nouveau lit</span>
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Modifier le lit' : 'Nouveau lit' }}</DialogTitle>
                <DialogDescription>Renseignez les informations du lit.</DialogDescription>
              </DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2">
                  <Label>Chambre</Label>
                  <select
                    v-model="formData.chambre_id"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                  >
                    <option value="">Sélectionner...</option>
                    <option v-for="c in chambres" :key="c.id" :value="c.id">
                      {{ c.pavillon?.name ? `${c.pavillon.name} — ` : '' }}{{ c.name }}
                    </option>
                  </select>
                </div>
                <div class="space-y-2">
                  <Label>Code</Label>
                  <Input v-model="formData.code" placeholder="Ex: L1" />
                </div>
                <div class="space-y-2">
                  <Label>Statut</Label>
                  <select
                    v-model="formData.status"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                    :disabled="isEditing && formData.status === 'occupe'"
                  >
                    <option value="libre">Libre</option>
                    <option value="occupe" disabled>Occupé (via affectation)</option>
                    <option value="hors_service">Hors service</option>
                  </select>
                </div>
              </div>
              <DialogFooter>
                <DialogClose as-child>
                  <Button variant="outline">Annuler</Button>
                </DialogClose>
                <Button @click="handleSubmit" :disabled="creating || updating" class="min-w-[120px]">
                  <IconifySpinner v-if="creating || updating" class="mr-2" />
                  {{ isEditing ? 'Modifier' : 'Créer' }}
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
                <TableHead>Code</TableHead>
                <TableHead>Chambre</TableHead>
                <TableHead>Pavillon</TableHead>
                <TableHead>Statut</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in filtered" :key="item.id">
                <TableCell class="font-medium">{{ item.code }}</TableCell>
                <TableCell>{{ item.chambre?.name || '—' }}</TableCell>
                <TableCell>{{ item.chambre?.pavillon?.name || '—' }}</TableCell>
                <TableCell>
                  <Badge :variant="statusVariant(item.status)">{{ statusLabel(item.status) }}</Badge>
                </TableCell>
                <TableCell class="text-right space-x-2">
                  <Button variant="outline" size="sm" @click="openEditModal(item)">Modifier</Button>
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
          Aucun lit trouvé
        </div>
      </BoxPanelWrapper>
    </div>
  </DashLayout>
</template>
