<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-14">#</th>
          <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isTeamTab ? '队伍' : '用户' }}</th>
          <th class="px-5 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">{{ isTeamTab ? '人数' : '解题数' }}</th>
          <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">总分</th>
          <th class="px-5 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">最后解题</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        <tr v-for="item in items" :key="item.id"
          class="transition-colors duration-150"
          :class="rowClass(item.id)">
          <!-- Rank -->
          <td class="px-5 py-4 whitespace-nowrap">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold"
              :class="rankBadgeClass(item.rank)">
              {{ rankDisplay(item.rank) }}
            </span>
          </td>

          <!-- Name / Avatar -->
          <td class="px-5 py-4 whitespace-nowrap">
            <div class="flex items-center gap-3">
              <!-- Avatar circle -->
              <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                :class="avatarClass(item)">
                {{ displayName(item).charAt(0).toUpperCase() }}
              </span>
              <div class="min-w-0">
                <router-link :to="userLink(item)"
                  class="font-semibold text-gray-900 hover:text-primary-600 transition-colors truncate block max-w-[160px]">
                  {{ displayName(item) }}
                </router-link>
                <span v-if="item.owner_name" class="text-xs text-gray-400">队长 {{ item.owner_name }}</span>
              </div>
            </div>
          </td>

          <!-- Solved / Members -->
          <td class="px-5 py-4 text-center whitespace-nowrap text-gray-600 font-medium">
            {{ displayStat(item) }}
          </td>

          <!-- Score -->
          <td class="px-5 py-4 text-right whitespace-nowrap">
            <span class="font-extrabold text-lg tabular-nums"
              :class="scoreClass(item.rank)">{{ item.score }}</span>
          </td>

          <!-- Last solve -->
          <td class="px-5 py-4 text-right whitespace-nowrap text-gray-400 text-sm hidden md:table-cell">
            {{ item.last_solve_at ? timeAgo(item.last_solve_at) : '-' }}
          </td>
        </tr>

        <!-- Empty -->
        <tr v-if="!items.length">
          <td colspan="5" class="px-5 py-12 text-center text-gray-400">暂无数据</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  items: { type: Array, default: () => [] },
  highlightId: { type: [Number, String], default: null },
  isTeamTab: { type: Boolean, default: false },
})

/* ---------- Helpers ---------- */
function displayName(item) {
  return item.username || item.name || '未知'
}
function displayStat(item) {
  return item.solved_count ?? item.member_count ?? '-'
}

function userLink(item) {
  if (item.username) return `/profile/${item.id}`
  return `/teams`
}

function timeAgo(dateStr) {
  if (!dateStr) return '-'
  const now = new Date()
  const date = new Date(dateStr)
  const diff = Math.floor((now - date) / 1000)
  if (diff < 60) return '刚刚'
  if (diff < 3600) return `${Math.floor(diff / 60)}分钟前`
  if (diff < 86400) return `${Math.floor(diff / 3600)}小时前`
  return `${Math.floor(diff / 86400)}天前`
}

/* ---------- Dynamic classes ---------- */
function rowClass(id) {
  const base = 'hover:bg-gray-50/80'
  if (props.highlightId && id === props.highlightId) {
    return `${base} bg-primary-50/70 border-l-4 border-primary-500`
  }
  return base
}

function rankDisplay(rank) {
  if (rank === 1) return '🥇'
  if (rank === 2) return '🥈'
  if (rank === 3) return '🥉'
  return rank
}

function rankBadgeClass(rank) {
  if (rank <= 3) return ''
  return 'bg-gray-100 text-gray-600'
}

function scoreClass(rank) {
  if (rank === 1) return 'text-amber-500'
  if (rank === 2) return 'text-gray-500'
  if (rank === 3) return 'text-amber-700'
  return 'text-amber-600'
}

function avatarClass(item) {
  // Unique color based on name hash
  const name = displayName(item)
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  const colors = [
    'bg-primary-100 text-primary-700',
    'bg-emerald-100 text-emerald-700',
    'bg-violet-100 text-violet-700',
    'bg-rose-100 text-rose-700',
    'bg-cyan-100 text-cyan-700',
    'bg-orange-100 text-orange-700',
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
  ]
  return colors[Math.abs(hash) % colors.length]
}
</script>
