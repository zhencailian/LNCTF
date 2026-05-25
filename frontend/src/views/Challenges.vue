<template>
  <div>
    <!-- ================ Header ================ -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">🏴 题目列表</h1>
      <span class="text-sm text-gray-400">共 {{ store.pagination.total }} 题</span>
    </div>

    <!-- ================ Mobile: Category Scroll ================ -->
    <div class="lg:hidden overflow-x-auto pb-3 mb-5 -mx-1">
      <div class="flex gap-2 px-1">
        <button @click="selectCategory('')"
          class="cat-chip shrink-0"
          :class="{ 'cat-chip-active': !store.filters.category }">
          📋 全部
        </button>
        <button v-for="cat in store.categories" :key="cat.id"
          @click="selectCategory(cat.name)"
          class="cat-chip shrink-0"
          :class="{ 'cat-chip-active': store.filters.category === cat.name }">
          {{ cat.icon || '📁' }} {{ cat.name }}
        </button>
      </div>
    </div>

    <!-- ================ Desktop: Two-Column Layout ================ -->
    <div class="grid grid-cols-1 lg:grid-cols-[14rem_1fr] gap-6">

      <!-- ========== Left Sidebar ========== -->
      <aside class="hidden lg:block">
        <div class="bg-white rounded-xl border border-gray-200 p-3 sticky top-24">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3.5 py-2.5">
            分类筛选
          </div>

          <!-- All -->
          <button @click="selectCategory('')"
            class="sidebar-btn"
            :class="{ 'sidebar-btn-active': !store.filters.category }">
            <span class="sidebar-icon">📋</span>
            <span>全部分类</span>
          </button>

          <!-- Categories -->
          <button v-for="cat in store.categories" :key="cat.id"
            @click="selectCategory(cat.name)"
            class="sidebar-btn"
            :class="{ 'sidebar-btn-active': store.filters.category === cat.name }">
            <span class="sidebar-icon">{{ cat.icon || '📁' }}</span>
            <span class="flex-1 text-left">{{ cat.name }}</span>
            <span class="text-xs tabular-nums"
              :class="store.filters.category === cat.name ? 'text-primary-500 font-semibold' : 'text-gray-400'">
              {{ cat.challenge_count ?? 0 }}
            </span>
          </button>
        </div>
      </aside>

      <!-- ========== Right Main Content ========== -->
      <div class="min-w-0">

        <!-- === Filter Toolbar === -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
          <div class="flex flex-wrap items-center gap-3">
            <!-- Difficulty -->
            <select v-model="store.filters.difficulty" @change="applyFilter"
              class="filter-select">
              <option value="">全部难度</option>
              <option value="easy">🟢 简单</option>
              <option value="medium">🟡 中等</option>
              <option value="hard">🔴 困难</option>
              <option value="expert">🟣 专家</option>
            </select>

            <!-- Status -->
            <select v-model="store.filters.status" @change="applyFilter"
              class="filter-select">
              <option value="">全部状态</option>
              <option value="solved">✅ 已解</option>
              <option value="unsolved">⬜ 未解</option>
            </select>

            <!-- Search -->
            <input v-model="searchKeyword" @keyup.enter="applyFilter"
              class="filter-input flex-1 min-w-[160px]"
              placeholder="搜索题目..." />

            <!-- Clear -->
            <button @click="clearFilters"
              class="filter-clear-btn">
              清除
            </button>
          </div>
        </div>

        <!-- === Loading State === -->
        <div v-if="store.loading" class="flex flex-col items-center py-20 text-gray-400">
          <div class="spinner w-8 h-8 border-[3px] border-primary-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="mt-4 text-sm">加载中...</p>
        </div>

        <!-- === Challenge Grid === -->
        <template v-else>
          <div v-if="store.challenges.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <ChallengeCard v-for="ch in store.challenges" :key="ch.id" :challenge="ch" />
          </div>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center py-20 text-gray-400">
            <span class="text-5xl mb-4">📭</span>
            <p class="text-sm">没有找到符合筛选条件的题目</p>
            <button @click="clearFilters" class="mt-4 text-sm text-primary-500 hover:text-primary-600 underline underline-offset-2">
              清除所有筛选条件
            </button>
          </div>
        </template>

        <!-- === Pagination === -->
        <div v-if="store.pagination.total > store.pagination.perPage"
          class="flex items-center justify-center gap-3 mt-8">
          <button @click="changePage(store.pagination.page - 1)"
            :disabled="store.pagination.page <= 1"
            class="page-btn">
            上一页
          </button>

          <!-- Page Numbers -->
          <div class="hidden sm:flex items-center gap-1">
            <button v-for="p in visiblePages" :key="p"
              @click="changePage(p)"
              class="page-num"
              :class="{ 'page-num-active': p === store.pagination.page }">
              {{ p }}
            </button>
          </div>

          <span class="sm:hidden text-sm text-gray-500">
            {{ store.pagination.page }} / {{ totalPages }}
          </span>

          <button @click="changePage(store.pagination.page + 1)"
            :disabled="store.pagination.page >= totalPages"
            class="page-btn">
            下一页
          </button>
        </div>

      </div><!-- /right content -->
    </div><!-- /two-column -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useChallengeStore } from '@/stores/challenges'
import ChallengeCard from '@/components/ChallengeCard.vue'

const route = useRoute()
const store = useChallengeStore()

const searchKeyword = ref(store.filters.keyword ?? '')

const totalPages = computed(() =>
  Math.ceil(store.pagination.total / store.pagination.perPage)
)

// 显示的页码按钮（最多 5 个）
const visiblePages = computed(() => {
  const current = store.pagination.page
  const total = totalPages.value
  if (total <= 5) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)
  if (end - start < 4) {
    if (start === 1) end = start + 4
    else start = end - 4
  }
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

function applyFilter() {
  store.filters.keyword = searchKeyword.value
  store.fetchChallenges(1)
}

function selectCategory(name) {
  store.filters.category = name
  applyFilter()
}

function clearFilters() {
  store.clearFilters()
  searchKeyword.value = ''
  store.fetchChallenges(1)
}

function changePage(page) {
  if (page < 1 || page > totalPages.value) return
  store.fetchChallenges(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(async () => {
  const catParam = route.query.category
  if (catParam) {
    store.filters.category = catParam
  }
  await store.fetchCategories()
  store.fetchChallenges()
})
</script>

<style scoped>
/* ========== Sidebar ========== */
.sidebar-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  color: #4b5563;
  transition: all 0.2s ease;
  cursor: pointer;
  border: 1px solid transparent;
}
.sidebar-btn:hover {
  background: #f9fafb;
  color: #111827;
}
.sidebar-btn-active {
  background: #eff6ff;
  color: #2563eb;
  font-weight: 600;
  border-color: #bfdbfe;
}

.sidebar-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.sidebar-btn-active .sidebar-icon {
  background: #dbeafe;
}

/* ========== Mobile Chips ========== */
.cat-chip {
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  border: 1px solid #e5e7eb;
  background: white;
  color: #4b5563;
  transition: all 0.2s ease;
  cursor: pointer;
}
.cat-chip:hover {
  border-color: #93c5fd;
  color: #2563eb;
}
.cat-chip-active {
  background: #eff6ff;
  border-color: #93c5fd;
  color: #2563eb;
  font-weight: 600;
}

/* ========== Filter Controls ========== */
.filter-select,
.filter-input {
  padding: 0.625rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  outline: none;
  background: white;
  transition: all 0.2s ease;
  color: #374151;
  min-height: 42px;
}
.filter-select:hover,
.filter-input:hover {
  border-color: #93c5fd;
}
.filter-select:focus,
.filter-input:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12), 0 1px 3px rgba(0, 0, 0, 0.04);
}

.filter-input::placeholder {
  color: #9ca3af;
}

.filter-clear-btn {
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  color: #6b7280;
  background: white;
  transition: all 0.2s ease;
  cursor: pointer;
  min-height: 42px;
}
.filter-clear-btn:hover {
  color: #374151;
  border-color: #9ca3af;
  background: #f9fafb;
}

/* ========== Pagination ========== */
.page-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  color: #374151;
  background: white;
  transition: all 0.2s ease;
  cursor: pointer;
}
.page-btn:hover:not(:disabled) {
  border-color: #93c5fd;
  color: #2563eb;
  background: #fafdff;
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-num {
  width: 36px;
  height: 36px;
  border-radius: 0.625rem;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #4b5563;
  transition: all 0.15s ease;
  cursor: pointer;
  border: 1px solid transparent;
}
.page-num:hover {
  background: #f3f4f6;
}
.page-num-active {
  background: #2563eb;
  color: white;
  font-weight: 600;
}
.page-num-active:hover {
  background: #1d4ed8;
}
</style>
