<template>
  <div>
    <!-- ================ Header ================ -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">🏆 排行榜</h1>
    </div>

    <!-- ================ Tab Switcher ================ -->
    <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-6">
      <button @click="store.switchTab('users')"
        class="px-4 py-2 rounded-md text-sm font-medium transition"
        :class="store.activeTab === 'users' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
        👤 个人排行
      </button>
      <button @click="store.switchTab('teams')"
        class="px-4 py-2 rounded-md text-sm font-medium transition"
        :class="store.activeTab === 'teams' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
        👥 队伍排行
      </button>
    </div>

    <!-- ================ Podium (only on page 1) ================ -->
    <div v-if="showPodium && podium.length > 0" class="mb-10">

      <!-- 2-col grid: 2nd+1st left, 3rd right on wider screens -->
      <!-- We position manually with justify-self for the V-shape -->
      <div class="grid grid-cols-3 gap-3 md:gap-5 max-w-2xl mx-auto items-end">

        <!-- ====== 2nd Place ====== -->
        <div v-if="podium[1]" class="justify-self-end podium-card podium-silver"
          style="padding-bottom: 1.25rem;">
          <!-- Crown for 2nd -->
          <div class="text-center mb-2">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 shadow-sm text-base">🥈</span>
          </div>
          <!-- Avatar -->
          <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold bg-gradient-to-br from-gray-200 to-gray-100 text-gray-600 shadow-md ring-2 ring-gray-300">
            {{ podiumName(podium[1]).charAt(0).toUpperCase() }}
          </div>
          <!-- Name -->
          <p class="mt-2 text-sm font-bold text-gray-800 text-center truncate max-w-[100px]">
            {{ podiumName(podium[1]) }}
          </p>
          <!-- Score -->
          <p class="text-sm font-extrabold text-gray-500 tabular-nums text-center">{{ podium[1].score }}</p>
        </div>

        <!-- ====== 1st Place ====== -->
        <div v-if="podium[0]" class="podium-card podium-gold"
          style="padding-bottom: 2rem;">
          <!-- Crown -->
          <div class="text-center mb-2">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-amber-200 to-yellow-400 shadow-md text-lg">👑</span>
          </div>
          <!-- Avatar -->
          <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold bg-gradient-to-br from-amber-200 to-yellow-300 text-amber-700 shadow-lg ring-2 ring-yellow-400">
            {{ podiumName(podium[0]).charAt(0).toUpperCase() }}
          </div>
          <!-- Name -->
          <p class="mt-2.5 text-base font-extrabold text-gray-900 text-center truncate max-w-[120px]">
            {{ podiumName(podium[0]) }}
          </p>
          <!-- Score -->
          <p class="text-lg font-black text-amber-500 tabular-nums text-center">{{ podium[0].score }}</p>
          <p class="text-[10px] text-amber-400 font-semibold uppercase tracking-wider text-center mt-0.5">第 1 名</p>
        </div>

        <!-- ====== 3rd Place ====== -->
        <div v-if="podium[2]" class="justify-self-start podium-card podium-bronze"
          style="padding-bottom: 0.75rem;">
          <!-- Crown -->
          <div class="text-center mb-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-100 to-amber-200 shadow-sm text-sm">🥉</span>
          </div>
          <!-- Avatar -->
          <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold bg-gradient-to-br from-amber-100 to-orange-200 text-amber-600 shadow-sm ring-2 ring-amber-300">
            {{ podiumName(podium[2]).charAt(0).toUpperCase() }}
          </div>
          <!-- Name -->
          <p class="mt-1.5 text-sm font-bold text-gray-700 text-center truncate max-w-[90px]">
            {{ podiumName(podium[2]) }}
          </p>
          <!-- Score -->
          <p class="text-sm font-extrabold text-amber-700 tabular-nums text-center">{{ podium[2].score }}</p>
        </div>

      </div>
    </div>

    <!-- ================ Loading ================ -->
    <div v-if="store.loading" class="flex flex-col items-center py-20 text-gray-400">
      <div class="spinner w-8 h-8 border-[3px] border-primary-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- ================ Table ================ -->
    <LeaderboardTable v-else
      :items="currentItems"
      :highlight-id="highlightId"
      :is-team-tab="store.activeTab === 'teams'" />

    <!-- ================ Pagination ================ -->
    <div v-if="store.pagination.total > store.pagination.perPage"
      class="flex items-center justify-center gap-3 mt-8">
      <button @click="changePage(store.pagination.page - 1)"
        :disabled="store.pagination.page <= 1"
        class="px-4 py-2 border border-gray-300 rounded-xl text-sm disabled:opacity-40 hover:border-primary-300 hover:text-primary-600 transition-all duration-200 bg-white">
        上一页
      </button>
      <!-- Page numbers -->
      <div class="hidden sm:flex items-center gap-1.5">
        <button v-for="p in visiblePages" :key="p"
          @click="changePage(p)"
          class="w-9 h-9 rounded-lg text-sm font-medium transition-all duration-150 flex items-center justify-center"
          :class="p === store.pagination.page ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100 border border-transparent'">
          {{ p }}
        </button>
      </div>
      <span class="sm:hidden text-sm text-gray-500">
        {{ store.pagination.page }} / {{ totalPages }}
      </span>
      <button @click="changePage(store.pagination.page + 1)"
        :disabled="store.pagination.page >= totalPages"
        class="px-4 py-2 border border-gray-300 rounded-xl text-sm disabled:opacity-40 hover:border-primary-300 hover:text-primary-600 transition-all duration-200 bg-white">
        下一页
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useLeaderboardStore } from '@/stores/leaderboard'
import LeaderboardTable from '@/components/LeaderboardTable.vue'

const auth = useAuthStore()
const store = useLeaderboardStore()

/* ---------- Pagination ---------- */
const totalPages = computed(() =>
  Math.ceil(store.pagination.total / store.pagination.perPage)
)

const visiblePages = computed(() => {
  const current = store.pagination.page
  const total = totalPages.value
  if (total <= 5) return Array.from({ length: total }, (_, i) => i + 1)
  let start = Math.max(1, current - 2)
  let end = Math.min(total, current + 2)
  if (end - start < 4) {
    if (start === 1) end = start + 4
    else start = end - 4
  }
  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

function changePage(page) {
  if (page < 1 || page > totalPages.value) return
  store.fetchLeaderboard(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

/* ---------- Podium ---------- */
const podium = computed(() => {
  // Podium only from page 1 data
  const items = currentItems.value
  // Return at most 3, filter to only include ranked 1-3
  return items.filter(i => i.rank <= 3).slice(0, 3)
})

const showPodium = computed(() => {
  return store.pagination.page === 1 && podium.value.length > 0
})

function podiumName(item) {
  return item.username || item.name || '未知'
}

/* ---------- Current items ---------- */
const currentItems = computed(() => {
  return store.activeTab === 'users' ? store.users : store.teams
})

/* ---------- Highlight ---------- */
const highlightId = computed(() => {
  if (store.activeTab === 'users') return auth.user?.id ?? null
  return auth.user?.team_id ?? null
})

onMounted(() => {
  store.fetchLeaderboard()
})
</script>

<style scoped>
/* ========== Podium Cards ========== */
.podium-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  padding: 1.25rem 1rem;
  width: 100%;
  max-width: 180px;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.25s ease;
}
.podium-card:hover {
  transform: translateY(-4px);
}

/* Gold glow */
.podium-gold {
  box-shadow:
    0 0 24px rgba(234, 179, 8, 0.18),
    0 0 48px rgba(234, 179, 8, 0.06);
}
.podium-gold:hover {
  box-shadow:
    0 0 32px rgba(234, 179, 8, 0.28),
    0 0 60px rgba(234, 179, 8, 0.10);
}
.podium-silver {
  box-shadow:
    0 0 20px rgba(148, 163, 184, 0.18),
    0 0 40px rgba(148, 163, 184, 0.06);
}
.podium-silver:hover {
  box-shadow:
    0 0 28px rgba(148, 163, 184, 0.28),
    0 0 50px rgba(148, 163, 184, 0.10);
}
.podium-bronze {
  box-shadow:
    0 0 16px rgba(180, 83, 9, 0.15),
    0 0 32px rgba(180, 83, 9, 0.05);
}
.podium-bronze:hover {
  box-shadow:
    0 0 24px rgba(180, 83, 9, 0.25),
    0 0 40px rgba(180, 83, 9, 0.08);
}
</style>
