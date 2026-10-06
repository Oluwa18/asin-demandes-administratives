<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AlertMessage from '../components/AlertMessage.vue'
import { api, errorMessage, TYPES_ACTE } from '../services/api'

const initialForm = () => ({ npi: '', type_acte: 'ACTE_NAISSANCE', nombre_copies: 1 })
const form = reactive(initialForm())
const loading = ref(false)
const error = ref('')
const created = ref(null)

async function submit() {
  loading.value = true
  error.value = ''
  created.value = null
  try {
    const { data } = await api.createDemande({ ...form, nombre_copies: Number(form.nombre_copies) })
    created.value = data
    Object.assign(form, initialForm())
  } catch (e) {
    error.value = errorMessage(e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-xl rounded-2xl border border-gray-200 bg-white p-6">
    <h2 class="mb-5 font-semibold text-gray-900">Déposer une demande</h2>

    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700" for="npi">NPI</label>
        <input id="npi" v-model.trim="form.npi" required inputmode="numeric" maxlength="10" placeholder="10 chiffres"
          class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:outline-none" />
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700" for="type">Type d'acte</label>
        <select id="type" v-model="form.type_acte"
          class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:outline-none">
          <option v-for="(label, key) in TYPES_ACTE" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700" for="copies">Nombre de copies</label>
        <select id="copies" v-model="form.nombre_copies"
          class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm focus:border-brand-500 focus:outline-none">
          <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>

      <AlertMessage :message="error" />
      <div v-if="created" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        Demande créée avec succès (référence {{ created.id.slice(0, 8) }}, statut {{ created.statut }}).
        <RouterLink :to="{ path: '/demandes', query: { npi: created.npi } }" class="font-medium underline">
          Voir les demandes de l'usager
        </RouterLink>
      </div>

      <button type="submit" :disabled="loading"
        class="h-11 w-full rounded-lg bg-brand-500 text-sm font-medium text-white hover:bg-brand-600 disabled:opacity-50">
        {{ loading ? 'Envoi…' : 'Déposer la demande' }}
      </button>
    </form>
  </div>
</template>
