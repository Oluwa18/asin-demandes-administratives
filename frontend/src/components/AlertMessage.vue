<script setup>
defineProps({ type: { type: String, default: 'error' }, message: { type: String, default: '' } })
</script>

<template>
  <div
    v-if="message"
    class="rounded-lg border px-4 py-3 text-sm"
    :class="type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'"
  >
    {{ message }}
  </div>
</template>
