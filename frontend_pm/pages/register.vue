<template>
  <v-container fluid class="fill-height">
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card elevation="8" class="pa-4">
          <v-card-title class="text-h4 text-center mb-4">
            Create Account
          </v-card-title>

          <v-card-text>
            <v-form ref="formRef" @submit.prevent="handleRegister">
              <v-text-field
                v-model="form.name"
                label="Full Name"
                prepend-inner-icon="mdi-account"
                :rules="[validators.required]"
                variant="outlined"
                class="mb-2"
              />

              <v-text-field
                v-model="form.email"
                label="Email"
                type="email"
                prepend-inner-icon="mdi-email"
                :rules="[validators.required, validators.email]"
                variant="outlined"
                class="mb-2"
              />

              <v-text-field
                v-model="form.password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                @click:append-inner="showPassword = !showPassword"
                :rules="[validators.required, validators.minLength(8)]"
                variant="outlined"
                class="mb-2"
              />

              <v-text-field
                v-model="form.password_confirmation"
                label="Confirm Password"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock-check"
                :rules="[validators.required, (value: string) => validators.passwordMatch(value, form.password)]"
                variant="outlined"
                class="mb-2"
              />

              <v-alert
                v-if="userStore.error"
                type="error"
                density="compact"
                class="mb-4"
                closable
                @click:close="userStore.clearError()"
              >
                {{ userStore.error }}
              </v-alert>

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="userStore.isLoading"
                :disabled="userStore.isLoading"
                class="mb-2"
              >
                Register
              </v-btn>

              <div class="text-center">
                <NuxtLink to="/login" class="text-primary text-decoration-none">
                  Already have an account? Login
                </NuxtLink>
              </div>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { useUserStore } from '~/stores/user'

definePageMeta({
  layout: false,
})

const userStore = useUserStore()
const router = useRouter()
const formRef = ref()
const showPassword = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const validators = {
  required: (value: string) => !!value || 'This field is required',
  email: (value: string) => {
    const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return pattern.test(value) || 'Invalid email address'
  },
  minLength: (min: number) => (value: string) => 
    value.length >= min || `Must be at least ${min} characters`,
  passwordMatch: (confirmPassword: string, password: string) => 
    confirmPassword === password || 'Passwords do not match'
}

const handleRegister = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  try {
    await userStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    await router.push('/')
  } catch (error) {
  }
}

onUnmounted(() => {
  userStore.clearError()
})

watch([() => form.name, () => form.email, () => form.password, () => form.password_confirmation], () => {
  if (userStore.error) {
    userStore.clearError()
  }
})
</script>

<style scoped>
.fill-height {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>