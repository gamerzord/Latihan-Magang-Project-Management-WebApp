export default defineNuxtPlugin(() => {
  const withCredentials = {
    credentials: 'include' as const,
    onRequest({ options }: any) {
      const token = useCookie('XSRF-TOKEN').value
      if (token) {
        options.headers = {
          ...options.headers,
          'X-XSRF-TOKEN': decodeURIComponent(token),
        }
      }
    },
  }

  globalThis.$fetch = $fetch.create(withCredentials)

  const originalUseFetch = useFetch

  // @ts-expect-error
  globalThis.useFetch = function (request: any, opts: any = {}) {
    return originalUseFetch(request, {
      ...withCredentials,
      ...opts,
      onRequest: (ctx: any) => {
        withCredentials.onRequest(ctx)
        opts.onRequest?.(ctx)
      },
    })
  }
})