export default defineNuxtRouteMiddleware(async (to, from) => {
  const userStore = useUserStore()

  const publicRoutes = ['/login', '/register']
  const isPublic = publicRoutes.includes(to.path)

  if (!userStore.isAuthenticated && !userStore.isLoading) {
    await userStore.checkAuth()
  }

  if (userStore.isAuthenticated && isPublic) {
    return navigateTo('/')
  }

  if (!userStore.isAuthenticated && !isPublic) {
    return navigateTo('/login')
  }
})
