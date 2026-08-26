<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import LayoutSaisieOperation from '@/components/templates/LayoutSaisieOperation.vue'
import BoxPanelWrapper from '@/components/atoms/BoxPanelWrapper.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { API_ROUTES } from '@/utils/constants/api_route'
import api from '@/services/api'
import { showCustomToast } from '@/utils/widgets/custom_toast'

const threads = ref<any[]>([])
const selectedId = ref<number | null>(null)
const thread = ref<any | null>(null)
const loading = ref(false)
const sending = ref(false)
const search = ref('')
const replyBody = ref('')
const chatBox = ref<HTMLElement | null>(null)

const loadThreads = async () => {
  loading.value = true
  try {
    const { data } = await api.get(API_ROUTES.SCHOOL_PARENT_FORUM_THREADS, {
      params: { search: search.value || undefined },
    })
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

const openThread = async (id: number) => {
  selectedId.value = id
  try {
    const { data } = await api.get(
      API_ROUTES.SCHOOL_PARENT_FORUM_THREAD.replace(':threadId', String(id)),
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

const sendReply = async () => {
  if (!selectedId.value || !replyBody.value.trim()) return
  sending.value = true
  try {
    await api.post(
      API_ROUTES.SCHOOL_PARENT_FORUM_REPLY.replace(':threadId', String(selectedId.value)),
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

const toggleStatus = async () => {
  if (!selectedId.value || !thread.value) return
  const url =
    thread.value.status === 'open'
      ? API_ROUTES.SCHOOL_PARENT_FORUM_CLOSE
      : API_ROUTES.SCHOOL_PARENT_FORUM_REOPEN
  try {
    await api.post(url.replace(':threadId', String(selectedId.value)))
    await openThread(selectedId.value)
    await loadThreads()
  } catch (e: any) {
    showCustomToast({
      message: e?.response?.data?.message || 'Action impossible',
      type: 'error',
    })
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

onMounted(loadThreads)
</script>

<template>
  <LayoutSaisieOperation
    group="operations"
    active-tag-name="forum-parents"
    :breadcrumb="[
      { label: 'Élèves', href: '/apprenants' },
      { label: 'Opérations', href: '/apprenants/operations' },
      { label: 'Forum parents', href: '/apprenants/operations/forum-parents' },
    ]"
  >
    <BoxPanelWrapper>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h2 class="text-lg font-medium">Forum parents</h2>
          <p class="text-sm text-foreground-muted">
            Répondez aux questions posées par les parents
          </p>
        </div>
        <div class="flex gap-2">
          <Input
            v-model="search"
            placeholder="Rechercher..."
            class="w-56 h-10"
            @keyup.enter="loadThreads"
          />
          <Button variant="outline" @click="loadThreads">Filtrer</Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-[520px]">
        <div class="rounded-md border bg-white overflow-hidden">
          <div v-if="loading" class="p-6 text-center text-gray-500">Chargement...</div>
          <div v-else-if="threads.length === 0" class="p-6 text-center text-gray-500 text-sm">
            Aucune discussion
          </div>
          <button
            v-for="t in threads"
            :key="t.id"
            type="button"
            class="w-full text-left px-4 py-3 border-b hover:bg-gray-50"
            :class="selectedId === t.id ? 'bg-blue-50' : ''"
            @click="selectedId = t.id"
          >
            <div class="flex justify-between gap-2">
              <span class="font-medium text-sm line-clamp-1">{{ t.subject }}</span>
              <span
                class="text-[10px] uppercase"
                :class="t.status === 'open' ? 'text-green-700' : 'text-gray-400'"
              >
                {{ t.status === 'open' ? 'Ouverte' : 'Clôturée' }}
              </span>
            </div>
            <p class="text-xs text-foreground-muted mt-1">
              {{ t.parent?.name || 'Parent' }}
              <span v-if="t.student"> · {{ t.student.name }}</span>
            </p>
            <p class="text-xs text-foreground-muted line-clamp-1 mt-0.5">
              {{ t.last_message?.body || '—' }}
            </p>
          </button>
        </div>

        <div class="rounded-md border bg-white flex flex-col lg:col-span-2 min-h-[520px]">
          <div v-if="!thread" class="flex-1 flex items-center justify-center text-gray-500">
            Sélectionnez une discussion
          </div>
          <template v-else>
            <div class="px-4 py-3 border-b flex flex-wrap items-start justify-between gap-2">
              <div>
                <h3 class="font-medium">{{ thread.subject }}</h3>
                <p class="text-xs text-foreground-muted mt-0.5">
                  {{ thread.parent?.name }}
                  <span v-if="thread.parent?.phone_number">
                    · {{ thread.parent.phone_number }}
                  </span>
                  <span v-if="thread.student"> · Élève : {{ thread.student.name }}</span>
                </p>
              </div>
              <Button variant="outline" size="sm" @click="toggleStatus">
                {{ thread.status === 'open' ? 'Clôturer' : 'Rouvrir' }}
              </Button>
            </div>
            <div ref="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/60">
              <div
                v-for="msg in thread.messages || []"
                :key="msg.id"
                class="flex"
                :class="msg.author_role === 'staff' ? 'justify-end' : 'justify-start'"
              >
                <div
                  class="max-w-[85%] rounded-lg px-3 py-2 text-sm shadow-sm"
                  :class="
                    msg.author_role === 'staff'
                      ? 'bg-emerald-700 text-white'
                      : 'bg-white border'
                  "
                >
                  <div class="text-[10px] mb-1 opacity-80">
                    {{ msg.author_role === 'staff' ? 'École' : msg.author_name || 'Parent' }}
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
                placeholder="Répondre au parent..."
                @keydown.ctrl.enter="sendReply"
              />
              <Button :disabled="sending || !replyBody.trim()" @click="sendReply">
                Répondre
              </Button>
            </div>
          </template>
        </div>
      </div>
    </BoxPanelWrapper>
  </LayoutSaisieOperation>
</template>
