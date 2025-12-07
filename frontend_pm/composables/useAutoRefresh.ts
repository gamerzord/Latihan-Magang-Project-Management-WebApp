export const useAutoRefresh = (fetchFunction: () => Promise<void>) => {
  const isActive = ref(true)

  const handleVisibilityChange = async () => {
    if (!document.hidden && isActive.value) {
      await fetchFunction()
    }
  }

  const resume = () => {
    isActive.value = true
    document.addEventListener('visibilitychange', handleVisibilityChange)
  }

  const pause = () => {
    isActive.value = false
    document.removeEventListener('visibilitychange', handleVisibilityChange)
  }

  onMounted(async () => {
    await fetchFunction()
    resume()
  })

  onUnmounted(() => {
    pause()
  })

  return { resume, pause, isActive: isActive }
}