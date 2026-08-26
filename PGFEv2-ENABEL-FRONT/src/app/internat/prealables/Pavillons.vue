<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import DashPageHeader from '@/components/templates/DashPageHeader.vue'
import { tagInternatNavPrealables } from '@/components/templates/internat/tags-links'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
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
    { label: 'Pavillons', isActive: true },
  ],
}

const activeTagName = 'pavillons'
const query = ref('')

const { data: rawData, loading: listLoading, fetchData: fetchPavillons } = useGetApi(
  API_ROUTES.GET_INTERNAT_PAVILLONS,
)
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

const filtered = computed(() => {
  if (!query.value) return items.value
  const s = query.value.toLowerCase()
  return items.value.filter((item: any) => item.name?.toLowerCase().includes(s))
})

const genderLabel = (g: string) => ({ mixte: 'Mixte', M: 'Masculin', F: 'Féminin' }[g] || g)

const isModalOpen = ref(false)
const isEditing = ref(false)
const currentId = ref<number | null>(null)
const formData = ref({ name: '', gender: 'mixte', notes: '' })

const resetForm = () => {
  formData.value = { name: '', gender: 'mixte', notes: '' }
  isEditing.value = false
  currentId.value = null
}

const openCreateModal = () => {
  resetForm()
  isModalOpen.value = true
}

const openEditModal = (item: any) => {
  formData.value = {
    name: item.name || '',
    gender: item.gender || 'mixte',
    notes: item.notes || '',
  }
  currentId.value = item.id
  isEditing.value = true
  isModalOpen.value = true
}

const handleSubmit = async () => {
  if (!formData.value.name.trim()) {
    showCustomToast({ message: 'Le nom est obligatoire', type: 'error' })
    return
  }

  if (isEditing.value && currentId.value) {
    await updateItem(API_ROUTES.UPDATE_INTERNAT_PAVILLON(currentId.value), formData.value)
    if (updateSuccess.value) {
      showCustomToast({ message: 'Pavillon mis à jour', type: 'success' })
      isModalOpen.value = false
      fetchPavillons()
    } else {
      showCustomToast({ message: updateError.value || 'Erreur lors de la mise à jour', type: 'error' })
    }
  } else {
    await createItem(API_ROUTES.CREATE_INTERNAT_PAVILLON, formData.value)
    if (createSuccess.value) {
      showCustomToast({ message: 'Pavillon créé', type: 'success' })
      isModalOpen.value = false
      fetchPavillons()
    } else {
      showCustomToast({ message: createError.value || 'Erreur lors de la création', type: 'error' })
    }
  }
}

const deletingId = ref<number | null>(null)
const handleDelete = async (id: number) => {
  deletingId.value = id
  await deleteApi(API_ROUTES.DELETE_INTERNAT_PAVILLON(id))
  deletingId.value = null
  if (deleteSuccess.value) {
    showCustomToast({ message: 'Pavillon supprimé', type: 'success' })
    fetchPavillons()
  } else {
    showCustomToast({ message: errorDelete.value || 'Erreur lors de la suppression', type: 'error' })
  }
}

onMounted(() => fetchPavillons())
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
        description="Gestion des pavillons"
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
                <span class="hidden sm:flex">Nouveau pavillon</span>
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>{{ isEditing ? 'Modifier le pavillon' : 'Nouveau pavillon' }}</DialogTitle>
                <DialogDescription>Renseignez les informations du pavillon.</DialogDescription>
              </DialogHeader>
              <div class="space-y-4 py-4">
                <div class="space-y-2">
                  <Label>Nom</Label>
                  <Input v-model="formData.name" placeholder="Ex: Pavillon A" />
                </div>
                <div class="space-y-2">
                  <Label>Genre</Label>
                  <select
                    v-model="formData.gender"
                    class="w-full h-10 rounded-md border border-input bg-background px-3 text-sm"
                  >
                    <option value="mixte">Mixte</option>
                    <option value="M">Masculin</option>
                    <option value="F">Féminin</option>
                  </select>
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
                <TableHead>Nom</TableHead>
                <TableHead>Genre</TableHead>
                <TableHead>Chambres</TableHead>
                <TableHead>Notes</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in filtered" :key="item.id">
                <TableCell class="font-medium">{{ item.name }}</TableCell>
                <TableCell>{{ genderLabel(item.gender) }}</TableCell>
                <TableCell>{{ item.chambres_count ?? 0 }}</TableCell>
                <TableCell class="max-w-[200px] truncate">{{ item.notes || '—' }}</TableCell>
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
          Aucun pavillon trouvé
        </div>
      </BoxPanelWrapper>
    </div>
  </DashLayout>
</template>
