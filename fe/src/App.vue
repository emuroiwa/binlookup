<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900">BIN Lookup System</h1>
          </div>
          <div class="flex space-x-8 items-center">
            <RouterLink 
              to="/" 
              class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium"
              :class="{ 'text-blue-600 border-b-2 border-blue-600': $route.name === 'bin-imports' }"
            >
              BIN Imports
            </RouterLink>
            <RouterLink 
              to="/bin-data" 
              class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium"
              :class="{ 'text-blue-600 border-b-2 border-blue-600': $route.name === 'bin-data' }"
            >
              BIN Data
            </RouterLink>
          </div>
        </div>
      </div>
    </nav>

    <RouterView />
  </div>
</template>
