import { createVuetify } from 'vuetify'
import { VCalendar } from 'vuetify/labs/VCalendar'

export default defineNuxtPlugin((app) => {
  const vuetify = createVuetify({
    ssr: true,
    components: {
      VCalendar,
    },
    theme: {
      defaultTheme: 'light',
      themes: {
        light: {
          colors: {
            primary: '#0079bf',
            secondary: '#5e6c84',
            accent: '#ff9f1a',
            error: '#eb5a46',
            warning: '#f2d600',
            info: '#00c2e0',
            success: '#61bd4f',
          }
        },
        dark: {
          colors: {
            primary: '#0079bf',
            secondary: '#5e6c84',
            accent: '#ff9f1a',
            error: '#eb5a46',
            warning: '#f2d600',
            info: '#00c2e0',
            success: '#61bd4f',
          }
        }
      }
    },
  })
  app.vueApp.use(vuetify)
})