<script setup lang="ts">
import DashLayout from '@/components/templates/DashLayout.vue'
import DashPageHeader from '@/components/templates/DashPageHeader.vue'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { onMounted, ref, computed, onBeforeUnmount } from 'vue'
import { useGetApi } from '@/composables/useGetApi.ts'
import { usePostApi } from '@/composables/usePostApi.ts'
import { usePutApi } from '@/composables/usePutApi.ts'
import { useDeleteApi } from '@/composables/useDeleteApi.ts'
import { API_ROUTES } from '@/utils/constants/api_route.ts'
import { eventBus } from '@/utils/eventBus.ts'
import { useAuthStore } from '@/stores/auth.ts'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { showCustomToast } from '@/utils/widgets/custom_toast.ts'
import IconifySpinner from '@/components/ui/spinner/IconifySpinner.vue'
import TableRowActions from '@/components/molecules/TableRowActions.vue'
import {
  AlertDialog,
  AlertDialogTrigger,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogCancel,
} from '@/components/ui/alert-dialog'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

interface SchoolYear {
  id: number
  name: string
  is_active?: boolean
  status?: string
  description?: string | null
  school_id?: number
}

const auth = useAuthStore()
const canActivate = computed(() => auth.can('schoolyears.activate'))
const canCreate = computed(() => auth.can('schoolyears.create'))
const canUpdate = computed(() => auth.can('schoolyears.update'))
const canDelete = computed(() => auth.can('schoolyears.delete'))

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Administration', href: '/admin' },
    { label: 'Années Scolaires', isActive: true },
  ],
}

const adminYearTags = [
  { name: 'schoolyears', text: 'Années Scolaires', href: '/admin/schoolyears' },
]
const activeTagName = computed(() => 'schoolyears')

const { data: rawData, loading, error, fetchData, lastResponseRaw } = useGetApi<any>(
  API_ROUTES.GET_SCHOOL_YEARS,
)
const { postData: createPost, loading: creating, error: createError } = usePostApi()
const { putData: activatePut, loading: activating, error: activateError } = usePutApi()
const { putData: updatePut, loading: updating, error: updateError } = usePutApi()
const { deleteItem, deleting, errorDelete: delError } = useDeleteApi()

const years = computed<SchoolYear[]>(() => {
  const raw = rawData.value ?? lastResponseRaw.value
  if (!raw) return []
  if (Array.isArray(raw)) return raw
  if (Array.isArray(raw.years)) return raw.years
  if (Array.isArray(raw.data)) return raw.data
  return []
})

const activatingId = ref<number | null>(null)
const deletingId = ref<number | null>(null)

function isActive(year: SchoolYear): boolean {
  return year.is_active === true || year.status === 'active'
}

async function onActivateYear(year: SchoolYear) {
  activatingId.value = year.id
  await activatePut(API_ROUTES.ACTIVATE_SCHOOL_YEAR(year.id), {})
  if (activateError.value) {
    showCustomToast({ message: activateError.value, type: 'error' })
  } else {
    showCustomToast({ message: `Année "${year.name}" activée avec succès`, type: 'success' })
    eventBus.emit('schoolYearsUpdated')
  }
  activatingId.value = null
}

// Création
const createOpen = ref(false)
const createName = ref('')
const createDescription = ref('')
const createActivate = ref(false)

function openCreateModal() {
  createName.value = ''
  createDescription.value = ''
  createActivate.value = false
  createOpen.value = true
}

async function onSubmitCreate() {
  const name = createName.value.trim()
  if (!name) {
    showCustomToast({ message: 'Le nom de l\'année est requis', type: 'error' })
    return
  }

  const payload: Record<string, unknown> = {
    name,
    is_active: createActivate.value,
  }
  if (auth.userSchoolId) {
    payload.school_id = Number(auth.userSchoolId)
  }
  const description = createDescription.value.trim()
  if (description) payload.description = description

  await createPost(API_ROUTES.CREATE_SCHOOL_YEAR, payload)
  if (createError.value) {
    showCustomToast({ message: createError.value, type: 'error' })
    return
  }
  showCustomToast({ message: 'Année scolaire créée avec succès', type: 'success' })
  createOpen.value = false
  eventBus.emit('schoolYearsUpdated')
}

// Édition
const editOpen = ref(false)
const editId = ref<number | null>(null)
const editName = ref('')
const editDescription = ref('')

function openEditModal(year: SchoolYear) {
  editId.value = year.id
  editName.value = year.name
  editDescription.value = year.description || ''
  editOpen.value = true
}

async function onSubmitEdit() {
  if (!editId.value) return
  const name = editName.value.trim()
  if (!name) {
    showCustomToast({ message: 'Le nom de l\'année est requis', type: 'error' })
    return
  }
  await updatePut(API_ROUTES.UPDATE_SCHOOL_YEAR(editId.value), {
    name,
    description: editDescription.value.trim() || null,
  })
  if (updateError.value) {
    showCustomToast({ message: updateError.value, type: 'error' })
    return
  }
  showCustomToast({ message: 'Année scolaire mise à jour', type: 'success' })
  editOpen.value = false
  eventBus.emit('schoolYearsUpdated')
}

async function onDeleteYear(year: SchoolYear) {
  deletingId.value = year.id
  const res = await deleteItem(API_ROUTES.DELETE_SCHOOL_YEAR(year.id))
  if (res) {
    showCustomToast({ message: `Année "${year.name}" supprimée`, type: 'success' })
    eventBus.emit('schoolYearsUpdated')
  } else {
    showCustomToast({ message: delError.value || 'Suppression impossible', type: 'error' })
  }
  deletingId.value = null
}

function onRefresh() {
  fetchData()
}

onMounted(() => {
  fetchData()
  eventBus.on('schoolYearsUpdated', onRefresh)
})

onBeforeUnmount(() => {
  eventBus.off('schoolYearsUpdated', onRefresh)
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/admin/schoolyears"
    module-name="admin"
  >
    <div class="pb-6 mx-auto w-full max-w-7xl">
      <DashPageHeader
        title="Administration"
        :tags="adminYearTags"
        :active-tag-name="activeTagName"
      />

      <BoxPanelWrapper>
        <div class="flex items-center gap-3 justify-between">
          <div class="relative flex-1">
            <!-- Pas de recherche textuelle sur les années, espace réservé pour l'alignement -->
          </div>
          <div class="flex items-center gap-2">
            <Button v-if="canCreate" @click="openCreateModal">
              <span class="iconify hugeicons--add-01"></span>
              <span>Nouvelle année</span>
            </Button>
          </div>
        </div>

        <div class="mt-4 flex-1 min-h-0 overflow-auto bg-white rounded-md border border-gray-100">
          <div v-if="loading" class="p-4 text-sm text-gray-600">Chargement...</div>
          <div v-else-if="error" class="p-4 text-sm text-red-600">{{ error }}</div>
          <template v-else>
            <div v-if="years.length === 0" class="p-4 text-sm text-gray-600">
              Aucune année scolaire trouvée.
            </div>
            <div v-else class="w-full">
              <div
                class="px-4 py-3 bg-gray-200 border-b border-gray-100 flex items-center text-sm font-semibold text-foreground-title"
              >
                <div class="w-12">N°</div>
                <div class="flex-1">Nom de l'année</div>
                <div class="w-36">Statut</div>
                <div class="w-24 text-right">Actions</div>
              </div>
              <ul class="divide-y">
                <li
                  v-for="(year, index) in years"
                  :key="year.id"
                  class="px-4 py-3 flex items-center text-sm"
                >
                  <div class="w-12 text-gray-500">{{ index + 1 }}</div>
                  <div class="flex-1 flex items-center gap-3">
                    <span class="iconify hugeicons--calendar-03 text-primary"></span>
                    <span class="font-medium">{{ year.name }}</span>
                  </div>
                  <div class="w-36">
                    <span
                      v-if="isActive(year)"
                      class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700"
                    >
                      <span class="iconify hugeicons--checkmark-circle-02 text-xs"></span>
                      Active
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500"
                    >
                      Inactive
                    </span>
                  </div>
                  <div class="w-24 flex justify-end">
                    <TableRowActions>
                      <template #actions>
                        <AlertDialog v-if="canActivate && !isActive(year)">
                          <AlertDialogTrigger as-child>
                            <Button
                              size="sm"
                              variant="outline"
                              class="h-8"
                              :disabled="activating && activatingId === year.id"
                            >
                              <span
                                v-if="activating && activatingId === year.id"
                                class="flex items-center gap-2"
                              >
                                <IconifySpinner size="sm" />
                              </span>
                              <span v-else class="iconify hugeicons--checkmark-circle-02"></span>
                              <span class="sr-only">Activer</span>
                            </Button>
                          </AlertDialogTrigger>
                          <AlertDialogContent>
                            <AlertDialogHeader>
                              <AlertDialogTitle>Activer cette année scolaire ?</AlertDialogTitle>
                              <AlertDialogDescription>
                                L'année <strong>{{ year.name }}</strong> deviendra l'année active.
                                Toutes les opérations seront liées à cette année.
                              </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                              <AlertDialogCancel>Annuler</AlertDialogCancel>
                              <Button @click="onActivateYear(year)">Confirmer</Button>
                            </AlertDialogFooter>
                          </AlertDialogContent>
                        </AlertDialog>
                        <Button
                          v-if="canUpdate"
                          size="sm"
                          variant="outline"
                          class="h-8"
                          @click="openEditModal(year)"
                        >
                          <span class="iconify hugeicons--edit-01"></span>
                          <span class="sr-only">Modifier</span>
                        </Button>
                        <AlertDialog v-if="canDelete && !isActive(year)">
                          <AlertDialogTrigger as-child>
                            <Button size="sm" variant="destructive" class="h-8">
                              <span class="iconify hugeicons--delete-02"></span>
                              <span class="sr-only">Supprimer</span>
                            </Button>
                          </AlertDialogTrigger>
                          <AlertDialogContent>
                            <AlertDialogHeader>
                              <AlertDialogTitle>Supprimer cette année ?</AlertDialogTitle>
                              <AlertDialogDescription>
                                L'année <strong>{{ year.name }}</strong> sera supprimée. Cette
                                action est irréversible.
                              </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                              <AlertDialogCancel>Annuler</AlertDialogCancel>
                              <Button
                                variant="destructive"
                                :disabled="deleting && deletingId === year.id"
                                @click="onDeleteYear(year)"
                              >
                                <span
                                  v-if="deleting && deletingId === year.id"
                                  class="flex items-center gap-2"
                                >
                                  <IconifySpinner size="sm" />
                                  <span>Suppression...</span>
                                </span>
                                <span v-else>Confirmer</span>
                              </Button>
                            </AlertDialogFooter>
                          </AlertDialogContent>
                        </AlertDialog>
                      </template>
                    </TableRowActions>
                  </div>
                </li>
              </ul>
            </div>
          </template>
        </div>
      </BoxPanelWrapper>
    </div>

    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-[420px]">
        <DialogHeader>
          <DialogTitle>Nouvelle année scolaire</DialogTitle>
          <DialogDescription>Créer une année pour l'école courante</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onSubmitCreate">
          <div class="grid gap-4 py-4">
            <div class="flex flex-col space-y-1.5">
              <Label for="year-name">Nom (ex. 2025-2026)</Label>
              <Input id="year-name" v-model="createName" class="h-10" required :disabled="creating" />
            </div>
            <div class="flex flex-col space-y-1.5">
              <Label for="year-desc">Description (optionnel)</Label>
              <Input id="year-desc" v-model="createDescription" class="h-10" :disabled="creating" />
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input v-model="createActivate" type="checkbox" class="rounded border-gray-300" :disabled="creating" />
              Activer immédiatement cette année
            </label>
          </div>
          <DialogFooter class="flex justify-end gap-2">
            <Button type="button" variant="outline" size="sm" :disabled="creating" @click="createOpen = false">
              Annuler
            </Button>
            <Button type="submit" size="sm" :disabled="creating">
              <span v-if="!creating" class="flex items-center gap-2">
                <span class="iconify hugeicons--floppy-disk"></span>
                <span>Enregistrer</span>
              </span>
              <span v-else class="flex items-center gap-2">
                <IconifySpinner size="lg" />
                <span>Création...</span>
              </span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
      <DialogContent class="sm:max-w-[420px]">
        <DialogHeader>
          <DialogTitle>Modifier l'année scolaire</DialogTitle>
          <DialogDescription>Mettre à jour le nom ou la description</DialogDescription>
        </DialogHeader>
        <form @submit.prevent="onSubmitEdit">
          <div class="grid gap-4 py-4">
            <div class="flex flex-col space-y-1.5">
              <Label for="edit-year-name">Nom</Label>
              <Input id="edit-year-name" v-model="editName" class="h-10" required :disabled="updating" />
            </div>
            <div class="flex flex-col space-y-1.5">
              <Label for="edit-year-desc">Description</Label>
              <Input id="edit-year-desc" v-model="editDescription" class="h-10" :disabled="updating" />
            </div>
          </div>
          <DialogFooter class="flex justify-end gap-2">
            <Button type="button" variant="outline" size="sm" :disabled="updating" @click="editOpen = false">
              Annuler
            </Button>
            <Button type="submit" size="sm" :disabled="updating">
              <span v-if="!updating" class="flex items-center gap-2">
                <span class="iconify hugeicons--floppy-disk"></span>
                <span>Enregistrer</span>
              </span>
              <span v-else class="flex items-center gap-2">
                <IconifySpinner size="lg" />
                <span>Mise à jour...</span>
              </span>
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </DashLayout>
</template>

<style scoped></style>
