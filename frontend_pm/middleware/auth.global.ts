export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) {
  const userToken = useCookie('auth-token').value
  const adminToken = useCookie('admin-auth-token').value
  const token = userToken || adminToken

  const publicPages = ['/login', '/register', '/admin']

  if (publicPages.includes(to.path)) {
    if (token) return navigateTo('/')
    return
  }

  if (to.path === '/') {
    return
  }

  if (!token) return navigateTo('/login')
  }
})