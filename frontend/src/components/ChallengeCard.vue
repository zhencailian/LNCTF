<template>
  <router-link :to="`/challenges/${challenge.id}`"
    class="block bg-white rounded-xl border border-gray-200 p-5 card-hover cursor-pointer"
    :class="{ 'status-solved': challenge.solved, 'status-unsolved': !challenge.solved }">
    <!-- 头部分类+难度 -->
    <div class="flex items-center justify-between mb-3">
      <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
        {{ challenge.category_icon || '📁' }} {{ challenge.category_name }}
      </span>
      <span class="text-xs font-bold px-2.5 py-1 rounded-full"
        :class="`diff-${challenge.difficulty}`">
        {{ difficultyLabel }}
      </span>
    </div>

    <!-- 标题 -->
    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-1">{{ challenge.title }}</h3>

    <!-- 底部分值+解题数 -->
    <div class="flex items-center justify-between text-sm">
      <div class="flex items-center gap-1">
        <span class="text-amber-500 font-bold">{{ challenge.score }}</span>
        <span class="text-gray-400">分</span>
      </div>
      <div class="flex items-center gap-3 text-gray-400">
        <span>🔥 {{ challenge.solve_count }} 人解出</span>
        <span v-if="challenge.solved" class="text-green-500 font-medium">✅ 已解</span>
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  challenge: { type: Object, required: true },
})

const difficultyLabel = computed(() => {
  const map = { easy: '简单', medium: '中等', hard: '困难', expert: '专家' }
  return map[props.challenge.difficulty] || props.challenge.difficulty
})
</script>
