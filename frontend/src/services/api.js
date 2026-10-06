import axios from 'axios'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

/** Transforme une erreur API en message lisible (validation Laravel incluse). */
export function errorMessage(error) {
  const data = error?.response?.data
  if (data?.errors) return Object.values(data.errors).flat().join(' ')
  if (data?.message) return data.message
  return "Impossible de joindre l'API. Vérifiez que le backend est démarré."
}

export const api = {
  createDemande: (payload) => http.post('/demandes', payload).then((r) => r.data),
  listDemandes: (npi, params) => http.get(`/usagers/${npi}/demandes`, { params }).then((r) => r.data),
  updateStatut: (id, payload) => http.patch(`/demandes/${id}/statut`, payload).then((r) => r.data),
  statistiques: () => http.get('/statistiques/statuts').then((r) => r.data),
}

export const TYPES_ACTE = {
  ACTE_NAISSANCE: 'Acte de naissance',
  CASIER_JUDICIAIRE: 'Casier judiciaire',
  CERTIFICAT_RESIDENCE: 'Certificat de résidence',
}

export const STATUTS = {
  DEPOSEE: 'Déposée',
  EN_COURS: 'En cours',
  VALIDEE: 'Validée',
  REJETEE: 'Rejetée',
}
