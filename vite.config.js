import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  // Serve from the nested frontend folder on Hostinger (no /qsr prefix)
  base: '/Tap-N-Eat/frontend/',
  plugins: [react()],
})
