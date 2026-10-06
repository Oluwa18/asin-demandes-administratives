<script setup>
defineProps({ statut: { type: String, required: true } })

const styles = {
  DEPOSEE: 'bg-gray-100 text-gray-700',
  EN_COURS: 'bg-amber-50 text-amber-700',
  VALIDEE: 'bg-emerald-50 text-emerald-700',
  REJETEE: 'bg-red-50 text-red-700',
}
</script>

<template>
  <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="styles[statut]">
    {{ statut }}
  </span>
</template>
