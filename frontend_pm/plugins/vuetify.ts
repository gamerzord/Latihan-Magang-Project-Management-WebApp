import { createVuetify } from 'vuetify'
import { VCalendar } from 'vuetify/labs/VCalendar'

export default defineNuxtPlugin((app) => {
  const vuetify = createVuetify({
    components: {
      VCalendar,
    },
    ssr: true,
  })
  app.vueApp.use(vuetify)
})