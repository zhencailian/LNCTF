<template>
  <div>
    <!-- ==================== Welcome Header ==================== -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
      <div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
          欢迎回来，
          <span class="text-primary-600">{{ auth.username }}</span>
          <span class="wave inline-block ml-1 origin-70">👋</span>
        </h1>
        <p class="mt-2 text-gray-500">
          继续你的 CTF 训练之旅
          <span class="mx-1.5 text-gray-300">·</span>
          <span class="font-semibold text-primary-600">{{ stats.score }}</span> 分
          <span class="mx-1.5 text-gray-300">·</span>
          <span class="font-semibold text-emerald-600">{{ stats.solved }}</span> 题已解
        </p>
      </div>
      <div v-if="stats.rank" class="rank-pill">
        <span>🏆</span>
        <span class="font-semibold">#{{ stats.rank }}</span>
      </div>
    </div>

    <!-- ==================== Announcement Ticker ==================== -->
    <div v-if="importantAnnouncements.length > 0" class="notice-ticker">
      <div class="flex items-center gap-3">
        <span class="shrink-0 inline-flex items-center gap-1.5 text-sm font-semibold text-gray-700">
          <span>📢</span>
          <span class="hidden sm:inline">公告</span>
        </span>
        <div class="flex-1 min-w-0 overflow-hidden" style="height: 1.25rem;">
          <transition name="ticker" mode="out-in">
            <p class="text-sm text-gray-600 truncate" :key="tickerIndex">
              <span class="inline-block text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium mr-1.5">重要</span>
              <span class="font-medium text-gray-800">{{ importantAnnouncements[tickerIndex]?.title }}</span>
            </p>
          </transition>
        </div>
        <span class="shrink-0 text-xs text-gray-400 tabular-nums">{{ tickerIndex + 1 }}/{{ importantAnnouncements.length }}</span>
      </div>
    </div>

    <!-- ==================== Stats Cards ==================== -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <!-- 总分 -->
      <div class="stat-card stat-glow-blue">
        <div class="flex items-center gap-3.5">
          <div class="stat-icon icon-blue">⚡</div>
          <div>
            <div class="stat-value text-blue-600">{{ stats.score }}</div>
            <div class="stat-label">当前总分</div>
          </div>
        </div>
      </div>
      <!-- 已解题数 -->
      <div class="stat-card stat-glow-emerald">
        <div class="flex items-center gap-3.5">
          <div class="stat-icon icon-emerald">🏁</div>
          <div>
            <div class="stat-value text-emerald-600">{{ stats.solved }}</div>
            <div class="stat-label">已解题数</div>
          </div>
        </div>
      </div>
      <!-- 总题目数 -->
      <div class="stat-card stat-glow-violet">
        <div class="flex items-center gap-3.5">
          <div class="stat-icon icon-violet">🎯</div>
          <div>
            <div class="stat-value text-violet-600">{{ stats.totalChallenges }}</div>
            <div class="stat-label">总题目数</div>
          </div>
        </div>
      </div>
      <!-- 当前排名 -->
      <div class="stat-card stat-glow-amber">
        <div class="flex items-center gap-3.5">
          <div class="stat-icon icon-amber">🏆</div>
          <div>
            <div class="stat-value text-amber-600">{{ stats.rank || '-' }}</div>
            <div class="stat-label">当前排名</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== Announcements + Quick Links ==================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 公告列表 -->
      <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm announcements-card">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900 flex items-center gap-2">
            <span>📢</span> 最新公告
          </h2>
        </div>
        <div class="px-6 py-2 announcements-scroll">

          <!-- Loading -->
          <div v-if="loading" class="flex flex-col items-center py-8 text-gray-400">
            <div class="spinner w-6 h-6 border-[3px] border-primary-500 border-t-transparent rounded-full animate-spin mb-3"></div>
            <span class="text-sm">加载中...</span>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="py-8 text-center">
            <p class="text-sm text-red-500 mb-3">{{ error }}</p>
            <button @click="loadAnnouncements"
              class="text-xs text-primary-600 hover:text-primary-700 font-medium underline underline-offset-2">
              点击重试
            </button>
          </div>

          <!-- Empty -->
          <div v-else-if="announcements.length === 0" class="text-gray-400 text-sm py-8 text-center">
            暂无公告
          </div>

          <!-- Announcement List -->
          <div v-else>
            <div v-for="item in announcements" :key="item.id"
              @click="openAnnouncement(item)"
              class="py-3.5 border-b border-gray-50 last:border-0 cursor-pointer hover:bg-gray-50/60 -mx-2 px-2 rounded-lg transition-colors">
              <div class="flex items-start gap-3">
                <span v-if="item.is_important"
                  class="shrink-0 text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium border border-red-100 mt-0.5">
                  📌 置顶
                </span>
                <div class="flex-1 min-w-0">
                  <h3 class="font-medium text-gray-900">{{ item.title }}</h3>
                  <p class="text-sm text-gray-500 mt-1 line-clamp-2 leading-relaxed">{{ plainContent(item.content) }}</p>
                  <span class="text-xs text-gray-400 mt-1.5 block">{{ formatTime(item.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 快速入口 -->
      <div class="space-y-3.5">
        <router-link to="/challenges"
          class="quick-link">
          <div class="quick-link-icon">🏴</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900">题目列表</div>
            <div class="text-sm text-gray-500">开始挑战</div>
          </div>
          <span class="text-gray-300 text-lg">→</span>
        </router-link>
        <router-link to="/leaderboard"
          class="quick-link">
          <div class="quick-link-icon">🏆</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900">排行榜</div>
            <div class="text-sm text-gray-500">查看排名</div>
          </div>
          <span class="text-gray-300 text-lg">→</span>
        </router-link>
        <router-link to="/teams"
          class="quick-link">
          <div class="quick-link-icon">👥</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-gray-900">队伍</div>
            <div class="text-sm text-gray-500">组队参赛</div>
          </div>
          <span class="text-gray-300 text-lg">→</span>
        </router-link>
      </div>
    </div>

    <!-- ==================== Categories ==================== -->
    <div class="mt-6">
      <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <span>📂</span> 题目分类
      </h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        <router-link v-for="cat in categories" :key="cat.id"
          :to="`/challenges?category=${cat.name}`"
          class="cat-card">
          <div class="text-2xl mb-1.5">{{ cat.icon || '📁' }}</div>
          <div class="font-semibold text-sm text-gray-900">{{ cat.name }}</div>
          <div class="text-xs text-gray-400 mt-0.5">{{ cat.challenge_count ?? 0 }} 题</div>
        </router-link>
      </div>
    </div>

    <!-- ==================== Announcement Detail Modal ==================== -->
    <Teleport to="body">
      <transition name="modal">
        <div v-if="selectedAnnouncement" class="modal-overlay" @click.self="closeAnnouncement">
          <div class="modal-card">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-5">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                  <span v-if="selectedAnnouncement.is_important"
                    class="text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium border border-red-100">
                    📌 置顶
                  </span>
                  <span class="text-xs text-gray-400">{{ formatTime(selectedAnnouncement.created_at) }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ selectedAnnouncement.title }}</h2>
              </div>
              <button @click="closeAnnouncement"
                class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <span class="text-lg">✕</span>
              </button>
            </div>
            <!-- Content -->
            <div class="prose-custom text-gray-700 leading-relaxed whitespace-pre-wrap text-sm">
              {{ selectedAnnouncement.content }}
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useChallengeStore } from '@/stores/challenges'
import api from '@/api'

const auth = useAuthStore()
const challengeStore = useChallengeStore()

const stats = ref({ score: 0, solved: 0, totalChallenges: 0, rank: null })
const announcements = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref(null)

/* ---------- Ticker ---------- */
const tickerIndex = ref(0)
let tickerTimer = null

const importantAnnouncements = computed(() =>
  announcements.value.filter(a => a.is_important)
)

function startTicker() {
  stopTicker()
  if (importantAnnouncements.value.length > 1) {
    tickerTimer = setInterval(() => {
      tickerIndex.value = (tickerIndex.value + 1) % importantAnnouncements.value.length
    }, 5000)
  }
}
function stopTicker() {
  if (tickerTimer) {
    clearInterval(tickerTimer)
    tickerTimer = null
  }
}

/* ---------- Announcement Modal ---------- */
const selectedAnnouncement = ref(null)

function openAnnouncement(item) {
  selectedAnnouncement.value = item
  document.body.style.overflow = 'hidden'
}
function closeAnnouncement() {
  selectedAnnouncement.value = null
  document.body.style.overflow = ''
}

/* ---------- Helpers ---------- */
function plainContent(content) {
  if (!content) return ''
  // Strip Markdown syntax for the card summary
  return content
    .replace(/\*\*(.+?)\*\*/g, '$1')
    .replace(/_(.+?)_/g, '$1')
    .replace(/`(.+?)`/g, '$1')
    .replace(/#{1,6}\s/g, '')
    .replace(/\[(.+?)\]\(.+?\)/g, '$1')
    .replace(/\n{2,}/g, ' ')
    .replace(/\n/g, ' ')
    .trim()
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const pad = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/* ---------- Data Loading ---------- */
async function loadAnnouncements() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get('/announcements/list', { params: { limit: 5 } })
    announcements.value = res.data || []
    tickerIndex.value = 0
    if (importantAnnouncements.value.length > 0) startTicker()
  } catch (e) {
    console.error('加载公告失败:', e)
    error.value = '公告加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const me = await auth.fetchMe()
    stats.value.score = me.score || 0
    stats.value.solved = me.solved_count || 0

    const challengesRes = await api.get('/challenges/list', { params: { per_page: 1 } })
    stats.value.totalChallenges = challengesRes.data?.total || 0

    // Load announcements
    await loadAnnouncements()

    await challengeStore.fetchCategories()
    categories.value = challengeStore.categories

    const lbRes = await api.get('/leaderboard/index', { params: { per_page: 100 } })
    const users = lbRes.data?.items || []
    const idx = users.findIndex(u => u.id === me.id)
    if (idx >= 0) stats.value.rank = idx + 1
  } catch (e) {
    console.error('仪表盘加载失败:', e)
  }
})

onUnmounted(() => {
  stopTicker()
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* ========== Stat Cards ========== */
.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.25rem;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: default;
}
.stat-card:hover {
  transform: translateY(-3px);
}

/* Glow shadows */
.stat-glow-blue:hover {
  box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.15), 0 8px 25px -6px rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.25);
}
.stat-glow-emerald:hover {
  box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.15), 0 8px 25px -6px rgba(16, 185, 129, 0.2);
  border-color: rgba(16, 185, 129, 0.25);
}
.stat-glow-violet:hover {
  box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.15), 0 8px 25px -6px rgba(139, 92, 246, 0.2);
  border-color: rgba(139, 92, 246, 0.25);
}
.stat-glow-amber:hover {
  box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.15), 0 8px 25px -6px rgba(245, 158, 11, 0.2);
  border-color: rgba(245, 158, 11, 0.25);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}
.icon-blue   { background: #eff6ff; }
.icon-emerald { background: #ecfdf5; }
.icon-violet { background: #f5f3ff; }
.icon-amber  { background: #fffbeb; }

.stat-value {
  font-size: 1.875rem;
  font-weight: 800;
  line-height: 1;
}
.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

/* ========== Notice Ticker ========== */
.notice-ticker {
  margin-bottom: 1.5rem;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
}

/* Ticker fade-slide transition */
.ticker-enter-active,
.ticker-leave-active {
  transition: all 0.35s ease;
}
.ticker-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.ticker-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* ========== Rank Pill ========== */
.rank-pill {
  margin-top: 0.75rem;
  padding: 0.375rem 1rem;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 9999px;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  color: #b45309;
  white-space: nowrap;
}
@media (min-width: 768px) {
  .rank-pill { margin-top: 0; }
}

/* ========== Quick Links ========== */
.quick-link {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}
.quick-link:hover {
  transform: translateX(3px);
  border-color: #bfdbfe;
  box-shadow: 0 2px 12px rgba(59, 130, 246, 0.08);
}

.quick-link-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

/* ========== Category Cards ========== */
.cat-card {
  display: block;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1rem 0.75rem;
  text-align: center;
  transition: all 0.2s ease;
}
.cat-card:hover {
  transform: translateY(-2px);
  border-color: #bfdbfe;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
}

/* ========== Wave Animation ========== */
.wave {
  display: inline-block;
  animation: wave-anim 2.5s ease-in-out infinite;
  transform-origin: 70% 70%;
}
@keyframes wave-anim {
  0%, 100% { transform: rotate(0deg); }
  20% { transform: rotate(14deg); }
  40% { transform: rotate(-8deg); }
  60% { transform: rotate(6deg); }
  80% { transform: rotate(-2deg); }
}

/* ========== Announcement Modal ========== */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 3rem 1rem;
  overflow-y: auto;
}
.modal-card {
  background: white;
  border-radius: 1rem;
  padding: 1.75rem;
  max-width: 600px;
  width: 100%;
  margin-top: 2rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  position: relative;
}

/* Modal enter/leave */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.25s ease;
}
.modal-enter-active .modal-card,
.modal-leave-active .modal-card {
  transition: all 0.25s ease;
}
.modal-enter-from {
  opacity: 0;
}
.modal-enter-from .modal-card {
  transform: scale(0.95) translateY(10px);
  opacity: 0;
}
.modal-leave-to {
  opacity: 0;
}
.modal-leave-to .modal-card {
  transform: scale(0.97) translateY(5px);
  opacity: 0;
}

/* Prose-like content styling */
.prose-custom {
  line-height: 1.75;
}

/* ========== Announcement Card: fixed height + internal scroll on lg+ ========== */
@media (min-width: 1024px) {
  .announcements-card {
    height: 400px;
    display: flex;
    flex-direction: column;
  }
  .announcements-scroll {
    flex: 1;
    overflow-y: auto;
  }
}

/* ========== Custom scrollbar for the announcement list ========== */
.announcements-scroll::-webkit-scrollbar {
  width: 4px;
}
.announcements-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.announcements-scroll::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 9999px;
}
.announcements-scroll::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>
