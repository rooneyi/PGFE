<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative">
    <div class="fixed inset-0">
      <img src="/screen-pattern.png" alt="pattern background" class="size-full object-cover" />
    </div>
    <div class="w-full max-w-6xl flex flex-col lg:flex-row items-center gap-8 lg:gap-16 relative">
      <div class="flex-1 space-y-6 text-center lg:text-left">
        <!-- Logo -->
        <div class="flex justify-center max-w-md">
          <img src="/pgfe-logo.png" width="500" class="h-24 w-auto" />
        </div>

        <!-- Main Title -->
        <div class="max-w-md">
          <h1 class="text-3xl lg:text-4xl font-bold text-gray-800 leading-tight">
            PROGICIEL DE GESTION<br />
            FORMATION EMPLOI
          </h1>
        </div>

        <!-- Description -->
        <div class="space-y-4 max-w-md text-foreground text-sm leading-relaxed mx-auto lg:mx-0">
          <p>
            PGFER est un progiciel qui vous permet la gestion, la centralisation et la mesure de vos
            informations à partir de vos actions.
          </p>
          <p>
            c'est un outil complet pour les écoles, le centre de formation, mais également pour le
            suivi de l'insertion professionnelle des apprenants, tout en mettant aussi un point sur
            les personnels.
          </p>
        </div>
      </div>

      <!-- Right Side - Login Form -->
      <div class="w-full max-w-md">
        <Card class="bg-white rounded-lg p-6 lg:p-8">
          <!-- Form Header -->
          <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Connectez-vous</h2>
            <p class="text-gray-500 text-sm">saisissez les coordonnées</p>
          </div>

          <!-- Form -->
          <form class="space-y-5" @submit.prevent="login">
            <!-- Username Field -->
            <!-- Email -->
            <div>
              <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                Nom d'utilisateur
              </label>
              <div class="relative">
                <Input
                  v-model="email"
                  type="text"
                  id="username"
                  name="username"
                  placeholder="utilisateur"
                  class="w-full pr-10 border border-gray-200 transition-all"
                />
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                  <svg
                    class="w-5 h-5 text-primary"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                </div>
              </div>
              <p v-if="errors.email" class="text-red-600 text-sm mt-1">
                {{ errors.email }}
              </p>
            </div>

            <!-- Password Field -->
            <!-- Password -->
            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Mot de passe
              </label>
              <div class="relative">
                <Input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  id="password"
                  name="password"
                  placeholder="••••••••"
                  autocomplete="password"
                  class="w-full pr-10 outline-none transition-all"
                />
                <button
                  @click="showPassword = !showPassword"
                  type="button"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2"
                >
                  <svg
                    v-if="!showPassword"
                    class="w-5 h-5 text-blue-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                  <svg
                    v-else
                    class="w-5 h-5 text-blue-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 012.424-4.043M9.88 9.88a3 3 0 014.24 4.24m-6.99-6.99L4 4m16 16l-4.24-4.24"
                    />
                  </svg>
                </button>
              </div>
              <p v-if="errors.password" class="text-red-600 text-sm mt-1">
                {{ errors.password }}
              </p>
              <!--              <p class="text-xs text-gray-500 mt-1">
                utilisez au moins 8 caractères, en combinant lettres, chiffres et
                symboles.
              </p>-->
            </div>

            <!-- Submit Button -->
            <div
              v-if="loginError"
              class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
              role="alert"
            >
              {{ loginError }}
            </div>

            <Button type="submit" class="w-full mt-4" :disabled="loading">
              <span v-if="!loading">Se connecter</span>
              <span v-else class="flex items-center gap-2">
                <span class="iconify animate-spin hugeicons--loading-03 text-xl"></span>
                <span>Connexion en cours...</span>
              </span>
            </Button>

            <!-- TODO : implementer si on autorise les users à le faire
            <div class="text-center pt-2">
              <a href="#" class="text-blue-500 hover:text-blue-600 text-sm transition-colors duration-200">
                mot de passe oublié ?
              </a>
            </div> -->
          </form>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useForm, useField } from 'vee-validate'
import { z } from 'zod'
import { toTypedSchema } from '@vee-validate/zod'
import { API_ROUTES } from '@/utils/constants/api_route'
import { showCustomToast } from '@/utils/widgets/custom_toast'
import { useAuthStore } from '@/stores/auth'
import { usePostApi } from '@/composables/usePostApi'
import { useAuth } from '@/composables/useAuth'
const { loading, error, response, postData, success, errorDetails } = usePostApi()
const { getCsrfToken } = useAuth()
// Schéma de validation
const loginSchema = z.object({
  email: z
    .string({
      required_error: 'Veuillez saisir votre email',
    })
    .min(1, 'Veuillez saisir votre email')
    .email("Format d'email invalide"),
  password: z
    .string({
      required_error: 'Veuillez saisir votre mot de passe',
    })
    .min(8, 'Le mot de passe doit contenir au moins 8 caractères'),
})

// VeeValidate - initialisation du formulaire
const { handleSubmit, errors, resetForm, setFieldError } = useForm({
  validationSchema: toTypedSchema(loginSchema),
})

const { value: email } = useField('email')
const { value: password } = useField('password')

const router = useRouter()

const showPassword = ref(false)
const loginError = ref<string | null>(null)
const authStore = useAuthStore()

// Soumission
const login = handleSubmit(async (values) => {
  loginError.value = null
  try {
    await getCsrfToken()
    await postData(API_ROUTES.LOGIN, values)

    if (error.value) {
      const message = error.value || 'Email ou mot de passe incorrect.'
      loginError.value = message

      const fieldErrors = (errorDetails.value as any)?.errors
      if (fieldErrors?.email?.[0]) {
        setFieldError('email', fieldErrors.email[0])
      }
      if (fieldErrors?.password?.[0]) {
        setFieldError('password', fieldErrors.password[0])
      }

      showCustomToast({
        message,
        type: 'error',
      })
      return
    } else if (success.value) {
      showCustomToast({
        message: 'Connexion réussie !',
        type: 'success',
      })
      authStore.setSession({
        token: response.value.token,
        user: response.value.user,
        roles: response.value.roles,
        permissions: response.value.permissions,
      })
      const roles = response.value.roles || []
      if (roles.includes('parent') && !roles.some((r: string) => ['super-admin', 'admin-ecole', 'admin'].includes(r))) {
        router.push('/espace-parent')
      } else {
        router.push('/')
      }
      resetForm()
    }
  } catch (csrfError) {
    loginError.value = 'Impossible de contacter le serveur. Vérifiez que le backend est démarré.'
    showCustomToast({
      message: loginError.value,
      type: 'error',
    })
    console.error('Erreur CSRF:', csrfError)
  }
})
</script>
