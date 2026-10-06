import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import DemandesView from '../views/DemandesView.vue'
import NouvelleDemandeView from '../views/NouvelleDemandeView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'dashboard', component: DashboardView, meta: { title: 'Tableau de bord' } },
    { path: '/demandes', name: 'demandes', component: DemandesView, meta: { title: 'Demandes' } },
    { path: '/demandes/nouvelle', name: 'nouvelle', component: NouvelleDemandeView, meta: { title: 'Nouvelle demande' } },
  ],
})
