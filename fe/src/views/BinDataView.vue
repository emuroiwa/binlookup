<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-8">
        <div class="p-6 sm:p-8">
          <div class="sm:flex sm:items-center sm:justify-between">
            <div class="sm:flex-auto">
              <h1 class="text-2xl font-semibold text-gray-900">BIN Data</h1>
              <p class="mt-2 text-sm text-gray-700">
                Browse and filter processed BIN lookup results.
              </p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
              <button
                @click="handleExport"
                :disabled="binDataStore.loading || !binDataStore.hasData"
                class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export to Excel
              </button>
            </div>
          </div>

          <!-- Statistics -->
          <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="bg-blue-50 overflow-hidden rounded-lg p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-blue-600 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-blue-600 truncate">Total BINs</dt>
                    <dd class="text-lg font-medium text-blue-900">{{ binDataStore.stats.total_bins.toLocaleString() }}</dd>
                  </dl>
                </div>
              </div>
            </div>

            <div class="bg-green-50 overflow-hidden rounded-lg p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm3 1h6v4H7V5zm8 8v2a1 1 0 01-1 1H6a1 1 0 01-1-1v-2h10zM7 10h6v2H7v-2z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-green-600 truncate">Unique Banks</dt>
                    <dd class="text-lg font-medium text-green-900">{{ binDataStore.stats.unique_banks.toLocaleString() }}</dd>
                  </dl>
                </div>
              </div>
            </div>

            <div class="bg-purple-50 overflow-hidden rounded-lg p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-purple-600 rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-purple-600 truncate">Countries</dt>
                    <dd class="text-lg font-medium text-purple-900">{{ binDataStore.stats.unique_countries.toLocaleString() }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-8">
        <div class="p-6 sm:p-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">BIN Number</label>
              <input
                v-model="localFilters.bin"
                @input="debouncedFilter"
                type="text"
                placeholder="Search BIN..."
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Bank Name</label>
              <input
                v-model="localFilters.bank"
                @input="debouncedFilter"
                type="text"
                placeholder="Search bank..."
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Card Brand</label>
              <select
                v-model="localFilters.brand"
                @change="applyFilters"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">All Brands</option>
                <option v-for="brand in binDataStore.filterOptions.brands" :key="brand" :value="brand">
                  {{ brand }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Card Type</label>
              <select
                v-model="localFilters.type"
                @change="applyFilters"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">All Types</option>
                <option v-for="type in binDataStore.filterOptions.types" :key="type" :value="type">
                  {{ type }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Country</label>
              <select
                v-model="localFilters.country"
                @change="applyFilters"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">All Countries</option>
                <option v-for="country in binDataStore.filterOptions.countries" :key="country" :value="country">
                  {{ country }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Date From</label>
              <input
                v-model="localFilters.date_from"
                @change="applyFilters"
                type="date"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Date To</label>
              <input
                v-model="localFilters.date_to"
                @change="applyFilters"
                type="date"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>

            <div class="flex items-end">
              <button
                @click="clearAllFilters"
                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md text-sm"
              >
                Clear Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6 sm:p-8">
          <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">BIN Data Results</h3>
            <div class="mt-4 sm:mt-0 sm:ml-4 sm:flex-none">
              <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-700">Per page:</label>
                <select
                  v-model="localFilters.per_page"
                  @change="changePerPage"
                  class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                >
                  <option :value="15">15</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="binDataStore.loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-sm text-gray-500">Loading...</span>
          </div>

          <!-- Error State -->
          <div v-else-if="binDataStore.error" class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Error loading data</h3>
                <p class="mt-1 text-sm text-red-700">{{ binDataStore.error }}</p>
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <div v-else class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    @click="handleSort('bin_number')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>BIN Number</span>
                      <SortIcon :column="'bin_number'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                  <th
                    @click="handleSort('bank_name')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Bank Name</span>
                      <SortIcon :column="'bank_name'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                  <th
                    @click="handleSort('card_brand')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Card Brand</span>
                      <SortIcon :column="'card_brand'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                  <th
                    @click="handleSort('card_type')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Card Type</span>
                      <SortIcon :column="'card_type'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                  <th
                    @click="handleSort('country_name')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Country</span>
                      <SortIcon :column="'country_name'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Website</th>
                  <th
                    @click="handleSort('created_at')"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide cursor-pointer hover:bg-gray-100"
                  >
                    <div class="flex items-center space-x-1">
                      <span>Added</span>
                      <SortIcon :column="'created_at'" :current-sort="binDataStore.filters.sort" :direction="binDataStore.filters.direction" />
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in binDataStore.binData?.data" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                    {{ item.bin_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.bank_name || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span
                      v-if="item.card_brand"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      {{ item.card_brand }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ item.card_type || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <span v-if="item.country_code" class="mr-2">{{ getCountryFlag(item.country_code) }}</span>
                      {{ item.country_name || '-' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <a
                      v-if="item.website"
                      :href="item.website"
                      target="_blank"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      {{ item.website }}
                    </a>
                    <span v-else>-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(item.created_at) }}
                  </td>
                </tr>
                <tr v-if="!binDataStore.binData?.data.length">
                  <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                    No BIN data found matching your filters
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <PaginationComponent
            v-if="binDataStore.binData && binDataStore.totalPages > 1"
            :current-page="binDataStore.currentPage"
            :total-pages="binDataStore.totalPages"
            :total-items="binDataStore.binData.total"
            :per-page="binDataStore.filters.per_page"
            :from="binDataStore.binData.from"
            :to="binDataStore.binData.to"
            @page-change="handlePageChange"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useBinDataStore } from '@/stores/binData'
import SortIcon from '@/components/SortIcon.vue'
import PaginationComponent from '@/components/PaginationComponent.vue'

const binDataStore = useBinDataStore()

// Reactive references
const localFilters = ref({ ...binDataStore.filters })
let debounceTimeout: NodeJS.Timeout | null = null

// Use reactive store properties directly (don't destructure to maintain reactivity)

onMounted(async () => {
  await Promise.all([
    binDataStore.fetchFilterOptions(),
    binDataStore.fetchBinData()
  ])
})

// Watch for changes in store filters
watch(() => binDataStore.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })

function debouncedFilter() {
  if (debounceTimeout) {
    clearTimeout(debounceTimeout)
  }
  debounceTimeout = setTimeout(() => {
    applyFilters()
  }, 500)
}

async function applyFilters() {
  binDataStore.updateFilters(localFilters.value)
  await binDataStore.fetchBinData()
}

async function clearAllFilters() {
  binDataStore.clearFilters()
  localFilters.value = { ...binDataStore.filters }
  await binDataStore.fetchBinData()
}

async function changePerPage() {
  binDataStore.updateFilters({ per_page: localFilters.value.per_page, page: 1 })
  await binDataStore.fetchBinData()
}

async function handleSort(column: string) {
  binDataStore.setSort(column)
  await binDataStore.fetchBinData()
}

async function handlePageChange(page: number) {
  binDataStore.setPage(page)
  await binDataStore.fetchBinData()
}

async function handleExport() {
  try {
    await binDataStore.exportData('excel')
  } catch (error) {
    alert('Export failed. Please try again.')
  }
}

function getCountryFlag(countryCode: string): string {
  if (!countryCode || countryCode.length !== 2) return ''
  return String.fromCodePoint(
    ...[...countryCode.toUpperCase()].map(x => 0x1f1a5 + x.charCodeAt(0))
  )
}

function formatDate(dateString: string): string {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleDateString()
  } catch (error) {
    return dateString
  }
}
</script>