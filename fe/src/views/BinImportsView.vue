<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6 sm:p-8">
          <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
              <h1 class="text-2xl font-semibold text-gray-900">BIN Imports</h1>
              <p class="mt-2 text-sm text-gray-700">
                Upload CSV files containing BIN numbers to start the lookup process.
              </p>
            </div>
          </div>

          <!-- File Upload Section -->
          <div class="mt-8">
            <div 
              class="border-2 border-dashed rounded-lg p-6 transition-colors"
              :class="{
                'border-blue-400 bg-blue-50': dragOver,
                'border-gray-300': !dragOver
              }"
              @drop="handleDrop"
              @dragover="handleDragOver"
              @dragleave="handleDragLeave"
            >
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                  <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <div class="mt-4">
                  <div v-if="selectedFile" class="mb-4">
                    <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                    <p class="text-xs text-gray-500">{{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB</p>
                    <button 
                      @click="uploadFile" 
                      :disabled="isUploading"
                      class="mt-2 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                    >
                      <span v-if="isUploading">Uploading...</span>
                      <span v-else>Upload File</span>
                    </button>
                    <button 
                      @click="selectedFile = null" 
                      class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Cancel
                    </button>
                  </div>
                  <div v-else>
                    <label for="file-upload" class="cursor-pointer">
                      <span class="mt-2 block text-sm font-medium text-gray-900">
                        Drop CSV file here or 
                        <span class="text-blue-600 hover:text-blue-500">browse</span>
                      </span>
                      <input 
                        id="file-upload" 
                        name="file-upload" 
                        type="file" 
                        class="sr-only" 
                        accept=".csv,.txt"
                        @change="handleFileSelect"
                      >
                    </label>
                    <p class="mt-1 text-xs text-gray-500">CSV files up to 10MB</p>
                  </div>
                  
                  <!-- Upload Progress -->
                  <div v-if="isUploading" class="mt-4">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: uploadProgress + '%' }"
                      ></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ uploadProgress }}% uploaded</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Imports Table -->
          <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Imports</h3>
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">File</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Date</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="importItem in imports" :key="importItem.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ importItem.filename }}</div>
                      <div class="text-sm text-gray-500">{{ importItem.total_bins }} BINs</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="getStatusClass(importItem.status?.color || 'gray')">
                        {{ importItem.status?.label || 'Unknown' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap w-64">
                      <div class="w-full bg-gray-200 rounded-full h-2">
                        <div 
                          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                          :style="{ width: (importItem.progress_percentage || 0) + '%' }"
                        ></div>
                      </div>
                      <div class="text-xs text-gray-500 mt-1">
                        {{ importItem.progress_percentage || 0 }}% complete
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(importItem.created_at) }}
                    </td>
                  </tr>
                  <tr v-if="!imports.length">
                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                      No imports found. Upload a CSV file to get started.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useImportsStore } from '@/stores/imports'

const importsStore = useImportsStore()

const selectedFile = ref<File | null>(null)
const isUploading = ref(false)
const uploadProgress = ref(0)
const dragOver = ref(false)

const imports = ref<any[]>([])

onMounted(async () => {
  await loadImports()
})

async function loadImports() {
  try {
    const data = await importsStore.fetchImports()
    imports.value = data.data || []
  } catch (error) {
    console.error('Failed to load imports:', error)
  }
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
    selectedFile.value = file
  } else {
    alert('Please select a valid CSV file')
  }
}

function handleDrop(event: DragEvent) {
  event.preventDefault()
  dragOver.value = false
  const file = event.dataTransfer?.files[0]
  if (file && (file.type === 'text/csv' || file.name.endsWith('.csv'))) {
    selectedFile.value = file
  } else {
    alert('Please drop a valid CSV file')
  }
}

function handleDragOver(event: DragEvent) {
  event.preventDefault()
  dragOver.value = true
}

function handleDragLeave() {
  dragOver.value = false
}

async function uploadFile() {
  if (!selectedFile.value) return

  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    isUploading.value = true
    uploadProgress.value = 0

    await importsStore.uploadFile(formData, (progressEvent: ProgressEvent) => {
      uploadProgress.value = Math.round(
        (progressEvent.loaded * 100) / progressEvent.total
      )
    })

    selectedFile.value = null
    await loadImports()
  } catch (error) {
    console.error('Upload failed:', error)
    alert('Upload failed. Please try again.')
  } finally {
    isUploading.value = false
    uploadProgress.value = 0
  }
}

function getStatusClass(color: string) {
  const classes: Record<string, string> = {
    gray: 'bg-gray-100 text-gray-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    blue: 'bg-blue-100 text-blue-800',
    green: 'bg-green-100 text-green-800',
    red: 'bg-red-100 text-red-800'
  }
  return classes[color] || 'bg-gray-100 text-gray-800'
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  try {
    return new Date(dateString).toLocaleString()
  } catch (error) {
    return dateString
  }
}
</script>