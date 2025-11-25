<template>
  <v-container fluid class="fill-height">
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card elevation="8" class="pa-4">
          <v-card-title class="text-h4 text-center mb-4">
            Login to PM Web App
          </v-card-title>

          <v-card-text>
            <v-form ref="formRef" @submit.prevent="handleLogin">
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
                :rules="[validators.required]"
                variant="outlined"
                class="mb-2"
              />

              <ClientOnly>
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
              </ClientOnly>

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="userStore.isLoading"
                :disabled="userStore.isLoading"
                class="mb-2"
              >
                Login
              </v-btn>

              <div class="text-center">
                <NuxtLink to="/register" class="text-primary text-decoration-none">
                  Don't have an account? Register
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
const userStore = useUserStore()
const router = useRouter()
const formRef = ref()
const showPassword = ref(false)

definePageMeta({
  layout: false,
})

const form = reactive({
  email: '',
  password: ''
})

const validators = {
  required: (value: string) => !!value || 'This field is required',
  email: (value: string) => {
    const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
    return pattern.test(value) || 'Invalid email address'
  }
}

const handleLogin = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  try {
    await userStore.login({
      email: form.email,
      password: form.password
    })
    
    await router.push('/')
  } catch (error) {
  }
}

onUnmounted(() => {
  userStore.clearError()
})

watch([() => form.email, () => form.password], () => {
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