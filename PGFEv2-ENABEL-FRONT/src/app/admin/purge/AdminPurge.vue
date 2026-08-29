<script setup lang="ts">
import DashLayout from '@/components/templates/DashLayout.vue'
import DashPageHeader from '@/components/templates/DashPageHeader.vue'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.ts'
import { usePostApi } from '@/composables/usePostApi.ts'
import { API_ROUTES } from '@/utils/constants/api_route.ts'
import { showCustomToast } from '@/utils/widgets/custom_toast.ts'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import IconifySpinner from '@/components/ui/spinner/IconifySpinner.vue'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogCancel,
} from '@/components/ui/alert-dialog'

const auth = useAuthStore()
const router = useRouter()
const isSuperAdmin = computed(() => auth.hasRole('super-admin'))

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Administration', href: '/admin' },
    { label: 'Purge données', isActive: true },
  ],
}

const adminPurgeTags = [
  { name: 'purge', text: 'Purge données', href: '/admin/purge' },
]
const activeTagName = computed(() => 'purge')

const confirmation = ref('')
const dialogOpen = ref(false)
const { postData, loading, error, success } = usePostApi()

const canConfirm = computed(() => confirmation.value.trim() === 'PURGER')

function openConfirmDialog() {
  if (!isSuperAdmin.value) {
    showCustomToast({ message: 'Accès réservé au super-admin', type: 'error' })
    return
  }
  if (!canConfirm.value) {
    showCustomToast({
      message: 'Tapez exactement PURGER pour activer la confirmation',
      type: 'error',
    })
    return
  }
  dialogOpen.value = true
}

async function runPurge() {
  if (!canConfirm.value) return

  await postData(API_ROUTES.ADMIN_SYSTEM_PURGE, { confirmation: 'PURGER' })

  if (error.value || !success.value) {
    showCustomToast({
      message: error.value || 'Échec de la purge des données',
      type: 'error',
    })
    dialogOpen.value = false
    return
  }

  showCustomToast({
    message:
      'Données purgées. Pays, provinces, territoires et comptes admin conservés.',
    type: 'success',
  })
  dialogOpen.value = false
  confirmation.value = ''

  window.setTimeout(() => {
    router.push('/')
    window.location.reload()
  }, 800)
}
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/admin/purge"
    module-name="admin"
  >
    <div class="pb-6 mx-auto w-full max-w-7xl">
      <DashPageHeader
        title="Administration"
        :tags="adminPurgeTags"
        :active-tag-name="activeTagName"
      />

      <BoxPanelWrapper v-if="!isSuperAdmin">
        <p class="p-4 text-sm text-muted-foreground">
          Cette page est réservée au rôle super-admin.
        </p>
      </BoxPanelWrapper>

      <BoxPanelWrapper v-else>
        <div class="max-w-2xl space-y-6 p-2">
          <div class="rounded-md border border-red-300 bg-red-50 p-4">
            <h2 class="text-base font-semibold text-red-800">Action irréversible</h2>
            <p class="mt-2 text-sm text-red-700">
              Cette opération supprime presque toutes les données de l’application
              pour permettre un démarrage propre chez le client.
            </p>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-700">
              <li>
                <strong>Conservé :</strong> pays, provinces, territoires, communes,
                types, fonctions, rôles/permissions Spatie, comptes super-admin
                (et admin).
              </li>
              <li>
                <strong>Supprimé :</strong> écoles, élèves, années, parents, classes,
                paiements, forum, RH, compta opérationnelle, utilisateurs non-admin,
                etc.
              </li>
            </ul>
          </div>

          <div class="space-y-2">
            <Label for="purge-confirm">
              Pour confirmer, tapez
              <span class="font-mono font-semibold">PURGER</span>
            </Label>
            <Input
              id="purge-confirm"
              v-model="confirmation"
              autocomplete="off"
              placeholder="PURGER"
              class="max-w-xs font-mono"
            />
          </div>

          <Button
            variant="destructive"
            :disabled="!canConfirm || loading"
            @click="openConfirmDialog"
          >
            Purger les données
          </Button>
        </div>

        <AlertDialog :open="dialogOpen" @update:open="(v) => (dialogOpen = v)">
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Confirmer la purge totale ?</AlertDialogTitle>
              <AlertDialogDescription>
                Toutes les données opérationnelles seront effacées définitivement.
                Les référentiels géographiques et les comptes super-admin seront
                conservés. Cette action ne peut pas être annulée.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel :disabled="loading">Annuler</AlertDialogCancel>
              <Button variant="destructive" :disabled="loading" @click="runPurge">
                <IconifySpinner v-if="loading" class="mr-2" />
                Oui, purger maintenant
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </BoxPanelWrapper>
    </div>
  </DashLayout>
</template>
