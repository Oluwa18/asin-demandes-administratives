<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AlertMessage from '../components/AlertMessage.vue'
import { api, errorMessage, STATUTS } from '../services/api'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

const cardLabels = {
  DEPOSEE: 'Déposées',
  EN_COURS: 'En cours',
  VALIDEE: 'Validées',
  REJETEE: 'Rejetées',
}

const colors = {
  DEPOSEE: 'bg-gray-100 text-gray-700',
  EN_COURS: 'bg-amber-50 text-amber-600',
  VALIDEE: 'bg-emerald-50 text-emerald-600',
  REJETEE: 'bg-red-50 text-red-600',
}

onMounted(async () => {
  try {
    stats.value = await api.statistiques()
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <AlertMessage :message="error" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="key in Object.keys(STATUTS)" :key="key" class="rounded-2xl border border-gray-200 bg-white p-5">
        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium" :class="colors[key]">{{ key }}</span>
        <p class="mt-4 text-sm text-gray-500">{{ cardLabels[key] }}</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">
          <span v-if="loading" class="text-gray-300">…</span>
          <span v-else>{{ stats?.[key] ?? '–' }}</span>
        </p>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
      <h2 class="font-semibold text-gray-900">Accès rapide</h2>
      <div class="mt-4 flex flex-wrap gap-3">
        <RouterLink to="/demandes/nouvelle" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
          Nouvelle demande
        </RouterLink>
        <RouterLink to="/demandes" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
          Consulter les demandes d'un usager
        </RouterLink>
      </div>
    </div>
  </div>
</template>
