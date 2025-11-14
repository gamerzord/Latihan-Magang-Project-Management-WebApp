export const useAutoRefresh = (fetchFunction: () => Promise<void>) => {
  onMounted(async () => {
    await fetchFunction()
    
    document.addEventListener('visibilitychange', async () => {
      if (!document.hidden) {
        await fetchFunction()
      }
    })
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', fetchFunction)
  })
}