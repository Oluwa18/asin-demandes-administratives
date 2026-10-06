<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import AlertMessage from '../components/AlertMessage.vue'
import StatutBadge from '../components/StatutBadge.vue'
import { api, errorMessage, STATUTS, TYPES_ACTE } from '../services/api'

const route = useRoute()
const filters = reactive({ npi: route.query.npi ?? '', statut: '' })
const demandes = ref([])
const meta = ref(null)
const loading = ref(false)
const pendingId = ref(null)
const error = ref('')
const success = ref('')
const rejet = reactive({ open: false, demande: null, motif: '' })

async function load(page = 1) {
  if (!/^\d{10}$/.test(filters.npi)) {
    error.value = 'Le NPI doit contenir exactement 10 chiffres.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    const params = { page, limit: 20, ...(filters.statut && { statut: filters.statut }) }
    const res = await api.listDemandes(filters.npi, params)
    demandes.value = res.data
    meta.value = res.meta
  } catch (e) {
    error.value = errorMessage(e)
    demandes.value = []
    meta.value = null
  } finally {
    loading.value = false
  }
}

async function changeStatut(demande, statut, motifRejet) {
  pendingId.value = demande.id
  error.value = ''
  success.value = ''
  try {
    const payload = motifRejet ? { statut, motif_rejet: motifRejet } : { statut }
    await api.updateStatut(demande.id, payload)
    success.value = `Demande ${demande.id.slice(0, 8)} passée au statut ${statut}.`
    rejet.open = false
    await load(meta.value?.page ?? 1)
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    pendingId.value = null
  }
}

function openRejet(demande) {
  Object.assign(rejet, { open: true, demande, motif: '' })
}

onMounted(() => {
  if (filters.npi) load()
})
</script>

<template>
  <div class="space-y-5">
    <form class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 md:flex-row md:items-end" @submit.prevent="load(1)">
      <div class="flex-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700" for="npi">NPI</label>
        <input id="npi" v-model.trim="filters.npi" inputmode="numeric" maxlength="10" placeholder="1234567890"
          class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:outline-none" />
      </div>
      <div class="md:w-56">
        <label class="mb-1.5 block text-sm font-medium text-gray-700" for="statut">Statut</label>
        <select id="statut" v-model="filters.statut"
          class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:outline-none">
          <option value="">Tous</option>
          <option v-for="(label, key) in STATUTS" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <button type="submit" :disabled="loading"
        class="h-11 rounded-lg bg-brand-500 px-6 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
        {{ loading ? 'Recherche…' : 'Rechercher' }}
      </button>
    </form>

    <AlertMessage :message="error" />
    <AlertMessage type="success" :message="success" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3">Référence</th>
              <th class="px-4 py-3">NPI</th>
              <th class="px-4 py-3">Type d'acte</th>
              <th class="px-4 py-3">Copies</th>
              <th class="px-4 py-3">Statut</th>
              <th class="px-4 py-3">Motif du rejet</th>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="loading"><td colspan="8" class="px-4 py-8 text-center text-gray-400">Chargement…</td></tr>
            <tr v-else-if="!demandes.length">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                {{ meta ? 'Aucune demande pour ce NPI.' : 'Saisissez un NPI puis lancez la recherche.' }}
              </td>
            </tr>
            <tr v-for="d in demandes" v-else :key="d.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-xs" :title="d.id">{{ d.id.slice(0, 8) }}</td>
              <td class="px-4 py-3">{{ d.npi }}</td>
              <td class="px-4 py-3">{{ TYPES_ACTE[d.type_acte] ?? d.type_acte }}</td>
              <td class="px-4 py-3">{{ d.nombre_copies }}</td>
              <td class="px-4 py-3"><StatutBadge :statut="d.statut" /></td>
              <td class="px-4 py-3 text-gray-600">{{ d.motif_rejet ?? '—' }}</td>
              <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ new Date(d.created_at).toLocaleString('fr-FR') }}</td>
              <td class="whitespace-nowrap px-4 py-3">
                <div class="flex gap-2">
                  <button v-if="d.statut === 'DEPOSEE'" :disabled="pendingId === d.id"
                    class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-50"
                    @click="changeStatut(d, 'EN_COURS')">Passer en cours</button>
                  <template v-if="d.statut === 'EN_COURS'">
                    <button :disabled="pendingId === d.id"
                      class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-50"
                      @click="changeStatut(d, 'VALIDEE')">Valider</button>
                    <button :disabled="pendingId === d.id"
                      class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white disabled:opacity-50"
                      @click="openRejet(d)">Rejeter</button>
                  </template>
                  <span v-if="d.statut === 'VALIDEE' || d.statut === 'REJETEE'" class="text-xs text-gray-400">Traitement terminé</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta && meta.total > 0" class="flex items-center justify-between border-t border-gray-200 px-4 py-3 text-sm">
        <span class="text-gray-500">{{ meta.total }} demande(s) — page {{ meta.page }} / {{ meta.total_pages }}</span>
        <div class="flex gap-2">
          <button :disabled="meta.page <= 1 || loading" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40" @click="load(meta.page - 1)">Précédent</button>
          <button :disabled="meta.page >= meta.total_pages || loading" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40" @click="load(meta.page + 1)">Suivant</button>
        </div>
      </div>
    </div>

    <div v-if="rejet.open" class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/50 p-4">
      <form class="w-full max-w-md rounded-2xl bg-white p-6" @submit.prevent="changeStatut(rejet.demande, 'REJETEE', rejet.motif.trim())">
        <h3 class="text-lg font-semibold text-gray-900">Rejeter la demande</h3>
        <label class="mb-1.5 mt-4 block text-sm font-medium text-gray-700" for="motif">Motif du rejet</label>
        <textarea id="motif" v-model="rejet.motif" rows="3" required
          class="w-full rounded-lg border border-gray-300 p-3 text-sm focus:border-brand-500 focus:outline-none" />
        <div class="mt-5 flex justify-end gap-3">
          <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm" @click="rejet.open = false">Annuler</button>
          <button type="submit" :disabled="!rejet.motif.trim() || pendingId"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Confirmer le rejet</button>
        </div>
      </form>
    </div>
  </div>
</template>
