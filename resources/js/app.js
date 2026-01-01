import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import CoreuiVue from '@coreui/vue'
import '@coreui/coreui/dist/css/coreui.min.css'
// Vuetify
import { createVuetify } from 'vuetify'
import 'vuetify/styles'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import CIcon from '@coreui/icons-vue'
import * as icons from '@coreui/icons'

// pinia store
import { createPinia } from 'pinia'

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#3f51b5',
          secondary: '#03DAC6',
          error: '#B00020',
        }
      }
    }
  },
  defaults: {
    VBtn: {
      variant: 'flat',
      rounded: 'sm',
    },
    VTextField: {
      variant: 'outlined',
      density: 'compact',
    },
    VAutocomplete: {
      variant: 'outlined',
      density: 'compact',
    },
    VSelect: {
      variant: 'outlined',
      density: 'compact',
    },
  }
})

const pinia = createPinia();

const app = createApp(App)

app.component('CIcon', CIcon)
app.provide('icons', icons)

// Use both libraries
app.use(router)
app.use(CoreuiVue)
app.use(vuetify)
app.use(pinia)

app.mount('#app')
