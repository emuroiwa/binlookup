import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE_URL = 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

export interface BinDataItem {
  id: string
  bin_number: string
  bank_name: string | null
  card_brand: string | null
  card_type: string | null
  country_name: string | null
  country_code: string | null
  website: string | null
  created_at: string
}

export interface BinDataFilters {
  bin: string
  bank: string
  brand: string
  type: string
  country: string
  date_from: string
  date_to: string
  sort: string
  direction: 'asc' | 'desc'
  per_page: number
  page: number
}

export interface BinDataResponse {
  data: BinDataItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

export interface FilterOptions {
  brands: string[]
  types: string[]
  countries: string[]
}

export interface Stats {
  unique_banks: number
  unique_countries: number
  total_bins: number
}

export const useBinDataStore = defineStore('binData', () => {
  const binData = ref<BinDataResponse | null>(null)
  const filterOptions = ref<FilterOptions>({ brands: [], types: [], countries: [] })
  const stats = ref<Stats>({ unique_banks: 0, unique_countries: 0, total_bins: 0 })
  const loading = ref(false)
  const error = ref<string | null>(null)

  const filters = ref<BinDataFilters>({
    bin: '',
    bank: '',
    brand: '',
    type: '',
    country: '',
    date_from: '',
    date_to: '',
    sort: 'created_at',
    direction: 'desc',
    per_page: 15,
    page: 1
  })

  // Computed getters
  const hasData = computed(() => binData.value && binData.value.data.length > 0)
  const totalPages = computed(() => binData.value?.last_page || 1)
  const currentPage = computed(() => binData.value?.current_page || 1)

  async function fetchBinData() {
    loading.value = true
    error.value = null
    
    try {
      const params = new URLSearchParams()
      Object.entries(filters.value).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
          params.append(key, value.toString())
        }
      })

      const response = await api.get(`/bin-data?${params.toString()}`)
      // Laravel returns paginated data in this format: { data: [], links: {}, meta: {} }
      binData.value = response.data
      
      // Calculate stats from the total and available data
      const data = response.data.data || []
      const uniqueBanks = new Set(data.filter((item: BinDataItem) => item.bank_name).map((item: BinDataItem) => item.bank_name)).size
      const uniqueCountries = new Set(data.filter((item: BinDataItem) => item.country_name).map((item: BinDataItem) => item.country_name)).size
      
      stats.value = { 
        unique_banks: uniqueBanks, 
        unique_countries: uniqueCountries, 
        total_bins: response.data.meta?.total || 0 
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch BIN data'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchFilterOptions() {
    try {
      const response = await api.get('/bin-data/filter-options')
      filterOptions.value = response.data || { brands: [], types: [], countries: [] }
      return response.data
    } catch (err: any) {
      console.error('Failed to fetch filter options:', err)
      filterOptions.value = { brands: [], types: [], countries: [] }
    }
  }

  async function exportData(format: 'csv' | 'excel' = 'excel') {
    try {
      const params = new URLSearchParams()
      Object.entries(filters.value).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined && key !== 'per_page' && key !== 'page') {
          params.append(key, value.toString())
        }
      })

      const response = await api.get(`/bin-data/export?format=${format}&${params.toString()}`, {
        responseType: 'blob'
      })

      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      
      const extension = format === 'csv' ? 'csv' : 'xlsx'
      const timestamp = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '')
      link.download = `bin_data_export_${timestamp}.${extension}`
      
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to export data'
      throw err
    }
  }

  function updateFilters(newFilters: Partial<BinDataFilters>) {
    Object.assign(filters.value, newFilters)
    filters.value.page = 1 // Reset to first page when filtering
  }

  function clearFilters() {
    filters.value = {
      bin: '',
      bank: '',
      brand: '',
      type: '',
      country: '',
      date_from: '',
      date_to: '',
      sort: 'created_at',
      direction: 'desc',
      per_page: 15,
      page: 1
    }
  }

  function setPage(page: number) {
    filters.value.page = page
  }

  function setSort(column: string) {
    if (filters.value.sort === column) {
      filters.value.direction = filters.value.direction === 'asc' ? 'desc' : 'asc'
    } else {
      filters.value.sort = column
      filters.value.direction = 'asc'
    }
    filters.value.page = 1
  }

  return {
    binData,
    filterOptions,
    stats,
    loading,
    error,
    filters,
    hasData,
    totalPages,
    currentPage,
    fetchBinData,
    fetchFilterOptions,
    exportData,
    updateFilters,
    clearFilters,
    setPage,
    setSort,
  }
})