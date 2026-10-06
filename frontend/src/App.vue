<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'

const route = useRoute()
const sidebarOpen = ref(false)
const links = [
  { to: '/', label: 'Tableau de bord' },
  { to: '/demandes', label: 'Demandes' },
  { to: '/demandes/nouvelle', label: 'Nouvelle demande' },
]
</script>

<template>
  <div class="flex min-h-screen">
    <aside
      class="fixed inset-y-0 left-0 z-30 w-64 border-r border-gray-200 bg-white px-5 py-6 transition-transform lg:static lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="mb-8 flex items-center gap-2">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 font-bold text-white">A</span>
        <span class="text-lg font-semibold text-gray-900">Suivi des actes</span>
      </div>
      <p class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-400">Menu</p>
      <nav class="space-y-1">
        <RouterLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="block rounded-lg px-3 py-2.5 text-sm font-medium"
          :class="route.path === link.to ? 'bg-brand-50 text-brand-500' : 'text-gray-700 hover:bg-gray-100'"
          @click="sidebarOpen = false"
        >
          {{ link.label }}
        </RouterLink>
      </nav>
    </aside>

    <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-gray-900/40 lg:hidden" @click="sidebarOpen = false" />

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="sticky top-0 z-10 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-4 md:px-6">
        <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm lg:hidden" @click="sidebarOpen = true">Menu</button>
        <h1 class="text-lg font-semibold text-gray-900">{{ route.meta.title }}</h1>
      </header>
      <main class="mx-auto w-full max-w-7xl p-4 md:p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
