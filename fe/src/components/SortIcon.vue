<template>
  <span class="ml-1 inline-flex items-center">
    <svg 
      v-if="direction === 'asc'"
      class="w-4 h-4 text-gray-400"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path d="M5.293 7.707a1 1 0 011.414 0L10 4.414l3.293 3.293a1 1 0 001.414-1.414l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 000 1.414z"/>
    </svg>
    <svg 
      v-else-if="direction === 'desc'"
      class="w-4 h-4 text-gray-400"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path d="M14.707 12.293a1 1 0 01-1.414 0L10 15.586l-3.293-3.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l4-4a1 1 0 000-1.414z"/>
    </svg>
    <svg 
      v-else
      class="w-4 h-4 text-gray-300"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path d="M5 8l5-5 5 5H5zm0 4l5 5 5-5H5z"/>
    </svg>
  </span>
</template>

<script setup lang="ts">
interface Props {
  direction?: 'asc' | 'desc' | null
}

withDefaults(defineProps<Props>(), {
  direction: null
})
</script>