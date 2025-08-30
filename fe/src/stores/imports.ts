import { ref } from 'vue'
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

export interface BinImport {
  id: string
  filename: string
  total_bins: number
  processed_bins: number
  failed_bins: number
  status: {
    value: string
    label: string
    color: string
  }
  progress_percentage: number
  success_rate: number
  started_at: string | null
  completed_at: string | null
  created_at: string
  updated_at: string
}

export const useImportsStore = defineStore('imports', () => {
  const imports = ref([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchImports() {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/bin-imports')
      imports.value = response.data.data || []
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch imports'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function uploadFile(formData: FormData, onUploadProgress?: (progressEvent: ProgressEvent) => void) {
    loading.value = true
    error.value = null

    try {
      const response = await api.post('/bin-imports', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress,
      })
      
      // Refresh imports after successful upload
      await fetchImports()
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to upload file'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    imports,
    loading,
    error,
    fetchImports,
    uploadFile,
  }
})