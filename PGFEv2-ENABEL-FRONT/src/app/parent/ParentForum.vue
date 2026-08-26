<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import PageAnimationWrapper from '@/components/atoms/CAnimationWrapper.vue'
import DashLayout from '@/components/templates/DashLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { API_ROUTES } from '@/utils/constants/api_route'
import api from '@/services/api'
import { showCustomToast } from '@/utils/widgets/custom_toast'

const breadcrumbItems = {
  items: [
    { label: 'Accueil', href: '/', icon: 'hugeicons--home-01' },
    { label: 'Espace Parent', href: '/espace-parent' },
    { label: 'Forum école', isActive: true },
  ],
}

const threads = ref<any[]>([])
const children = ref<any[]>([])
const selectedId = ref<number | null>(null)
const thread = ref<any | null>(null)
const loading = ref(false)
const sending = ref(false)
const showNew = ref(false)

const newSubject = ref('')
const newBody = ref('')
const newStudentId = ref('')
const replyBody = ref('')
const chatBox = ref<HTMLElement | null>(null)

const selectedThread = computed(() =>
  threads.value.find((t) => t.id === selectedId.value) || null,
)

const loadThreads = async () => {
  loading.value = true
  try {
    const { data } = await api.get(API_ROUTES.PARENT_FORUM_THREADS)
    threads.value = Array.isArray(data) ? data : data?.data ?? []
  } catch (e: any) {
    showCustomToast({
      message: e?.response?.data?.message || 'Impossible de charger le forum',
      type: 'error',
    })
  } finally {
    loading.value = false
  }
}

const loadChildren = async () => {
  try {
    const { data } = await api.get(API_ROUTES.PARENT_CHILDREN)
    children.value = Array.isArray(data) ? data : data?.data ?? []
  } catch {
    children.value = []
  }
}

const openThread = async (id: number) => {
  selectedId.value = id
  try {
    const { data } = await api.get(
      API_ROUTES.PARENT_FORUM_THREAD.replace(':threadId', String(id)),
    )
    thread.value = data?.data ?? data
    await nextTick()
    if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight
  } catch (e: any) {
    showCustomToast({
      message: e?.response?.data?.message || 'Discussion introuvable',
      type: 'error',
    })
  }
}

const createThread = async () => {
  if (!newSubject.value.trim() || !newBody.value.trim()) {
    showCustomToast({ message: 'Sujet et message obligatoires', type: 'error' })
    return
  }
  sending.value = true
  try {
    const payload: any = {
      subject: newSubject.value.trim(),
      body: newBody.value.trim(),
    }
    if (newStudentId.value && newStudentId.value !== 'none') {
      payload.student_id = Number(newStudentId.value)
    }
    const { data } = await api.post(API_ROUTES.PARENT_FORUM_THREADS, payload)
    showCustomToast({ message: 'Question envoyée à l’école', type: 'success' })
    showNew.value = false
    newSubject.value = ''
    newBody.value = ''
    newStudentId.value = ''
    await loadThreads()
    const created = data?.data
    if (created?.id) await openThread(created.id)
  } catch (e: any) {
    showCustomToast({
      message: e?.response?.data?.message || 'Échec de l’envoi',
      type: 'error',
    })
  } finally {
    sending.value = false
  }
}

const sendReply = async () => {
  if (!selectedId.value || !replyBody.value.trim()) return
  if (thread.value?.status === 'closed') {
    showCustomToast({ message: 'Discussion clôturée', type: 'error' })
    return
  }
  sending.value = true
  try {
    await api.post(
      API_ROUTES.PARENT_FORUM_REPLY.replace(':threadId', String(selectedId.value)),
      { body: replyBody.value.trim() },
    )
    replyBody.value = ''
    await openThread(selectedId.value)
    await loadThreads()
  } catch (e: any) {
    showCustomToast({
      message: e?.response?.data?.message || 'Échec de l’envoi',
      type: 'error',
    })
  } finally {
    sending.value = false
  }
}

const formatDate = (iso?: string) => {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleString('fr-FR', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return iso
  }
}

watch(selectedId, (id) => {
  if (id) openThread(id)
})

onMounted(async () => {
  await Promise.all([loadThreads(), loadChildren()])
})
</script>

<template>
  <DashLayout
    :breadcrumb="breadcrumbItems"
    active-route="/espace-parent/forum"
    module-name="parent"
  >
    <PageAnimationWrapper class="mx-auto w-full max-w-7xl px-4 sm:px-8 md:px-10 lg:px-12">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold text-fg-title">Forum avec l’école</h1>
          <p class="text-sm text-foreground-muted mt-1">
            Posez vos questions et échangez avec l’équipe scolaire
          </p>
        </div>
        <Button @click="showNew = !showNew">
          {{ showNew ? 'Annuler' : 'Nouvelle question' }}
        </Button>
      </div>

      <div v-if="showNew" class="mb-6 rounded-md border bg-white p-5 space-y-3">
        <div class="space-y-1.5">
          <Label>Sujet</Label>
          <Input v-model="newSubject" placeholder="Ex. Absence, bulletin, réunion..." />
        </div>
        <div class="space-y-1.5">
          <Label>Enfant concerné (optionnel)</Label>
          <Select v-model="newStudentId">
            <SelectTrigger class="!h-10 bg-white w-full">
              <SelectValue placeholder="Tous / non précisé" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectItem value="none">Non précisé</SelectItem>
                <SelectItem
                  v-for="child in children"
                  :key="child.id"
                  :value="String(child.id)"
                >
                  {{ child.name }} {{ child.firstname }} {{ child.lastname }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1.5">
          <Label>Votre message</Label>
          <Textarea v-model="newBody" rows="4" placeholder="Écrivez votre question..." />
        </div>
        <Button :disabled="sending" @click="createThread">Envoyer</Button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-[480px]">
        <div class="rounded-md border bg-white overflow-hidden lg:col-span-1">
          <div class="px-4 py-3 border-b font-medium">Mes discussions</div>
          <div v-if="loading" class="p-6 text-center text-gray-500">Chargement...</div>
          <div v-else-if="threads.length === 0" class="p-6 text-center text-gray-500 text-sm">
            Aucune question pour le moment
          </div>
          <button
            v-for="t in threads"
            :key="t.id"
            type="button"
            class="w-full text-left px-4 py-3 border-b hover:bg-gray-50 transition"
            :class="selectedId === t.id ? 'bg-blue-50' : ''"
            @click="selectedId = t.id"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="font-medium text-sm line-clamp-1">{{ t.subject }}</span>
              <span
                class="text-[10px] uppercase tracking-wide"
                :class="t.status === 'open' ? 'text-green-700' : 'text-gray-400'"
              >
                {{ t.status === 'open' ? 'Ouverte' : 'Clôturée' }}
              </span>
            </div>
            <p class="text-xs text-foreground-muted mt-1 line-clamp-2">
              {{ t.last_message?.body || t.student?.name || '—' }}
            </p>
          </button>
        </div>

        <div class="rounded-md border bg-white flex flex-col lg:col-span-2 min-h-[480px]">
          <div v-if="!thread" class="flex-1 flex items-center justify-center text-gray-500 p-8">
            Sélectionnez une discussion ou créez une nouvelle question
          </div>
          <template v-else>
            <div class="px-4 py-3 border-b">
              <h2 class="font-medium">{{ thread.subject }}</h2>
              <p class="text-xs text-foreground-muted mt-0.5">
                <span v-if="thread.student">Enfant : {{ thread.student.name }} · </span>
                {{ thread.status === 'open' ? 'Ouverte' : 'Clôturée' }}
              </p>
            </div>
            <div ref="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/60">
              <div
                v-for="msg in thread.messages || []"
                :key="msg.id"
                class="flex"
                :class="msg.author_role === 'parent' ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[85%] rounded-lg px-3 py-2 text-sm shadow-sm"
                  :class="
                    msg.author_role === 'parent'
                      ? 'bg-blue-600 text-white'
                      : 'bg-white border text-fg-title'
                  "
                >
                  <div
                    class="text-[10px] mb-1 opacity-80"
                    :class="msg.author_role === 'parent' ? 'text-blue-100' : 'text-foreground-muted'"
                  >
                    {{ msg.author_role === 'parent' ? 'Vous' : msg.author_name || 'École' }}
                    · {{ formatDate(msg.created_at) }}
                  </div>
                  <div class="whitespace-pre-wrap">{{ msg.body }}</div>
                </div>
              </div>
            </div>
            <div v-if="thread.status === 'open'" class="p-3 border-t flex gap-2">
              <Textarea
                v-model="replyBody"
                rows="2"
                class="flex-1"
                placeholder="Écrire un message..."
                @keydown.ctrl.enter="sendReply"
              />
              <Button :disabled="sending || !replyBody.trim()" @click="sendReply">
                Envoyer
              </Button>
            </div>
            <div v-else class="p-3 border-t text-sm text-center text-gray-500">
              Discussion clôturée par l’école
            </div>
          </template>
        </div>
      </div>
    </PageAnimationWrapper>
  </DashLayout>
</template>
