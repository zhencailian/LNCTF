<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <h4 class="font-semibold text-gray-700 mb-3">🚩 Flag 提交</h4>

    <!-- 提交表单 -->
    <div class="flex gap-2">
      <input v-model="flagInput"
        type="text"
        placeholder="flag{...}"
        class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none font-mono text-sm"
        :disabled="submitting || cooldown > 0"
        @keyup.enter="submitFlag"
      />
      <button @click="submitFlag"
        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 text-white rounded-lg font-medium transition"
        :disabled="submitting || cooldown > 0 || !flagInput.trim()">
        <span v-if="submitting" class="spinner inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
        <span v-else-if="cooldown > 0">{{ cooldown }}s</span>
        <span v-else>提交</span>
      </button>
    </div>

    <!-- 反馈信息 -->
    <div v-if="feedback" class="mt-3 p-3 rounded-lg text-sm font-medium"
      :class="feedback.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
      <div class="flex items-center gap-2">
        <span>{{ feedback.type === 'success' ? '✅' : '❌' }}</span>
        <span>{{ feedback.message }}</span>
      </div>
      <div v-if="feedback.score" class="mt-1 text-xs opacity-75">
        获得 {{ feedback.score }} 分 {{ feedback.isFirstBlood ? '🏆 一血奖励！' : '' }}
      </div>
    </div>

    <!-- 已解题提示 -->
    <div v-if="alreadySolved" class="mt-3 p-3 rounded-lg bg-green-50 text-green-700 border border-green-200 text-sm font-medium">
      ✅ 你已经解过这道题了
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/api'

const props = defineProps({
  challengeId: { type: Number, required: true },
  solved: { type: Boolean, default: false },
})

const emit = defineEmits(['solved'])

const flagInput = ref('')
const submitting = ref(false)
const cooldown = ref(0)
const feedback = ref(null)
const alreadySolved = computed(() => props.solved)

async function submitFlag() {
  if (!flagInput.value.trim() || submitting.value || cooldown.value > 0) return

  submitting.value = true
  feedback.value = null

  try {
    const res = await api.post('/submissions/submit', {
      challenge_id: props.challengeId,
      flag: flagInput.value.trim(),
    })
    feedback.value = {
      type: 'success',
      message: '🎉 Flag 正确！',
      score: res.data?.score_earned,
      isFirstBlood: res.data?.is_first_blood,
    }
    flagInput.value = ''
    emit('solved', res.data)
  } catch (e) {
    feedback.value = {
      type: 'error',
      message: e.message || 'Flag 错误，请重试',
    }
    // 限流冷却
    if (e.message?.includes('频繁')) {
      startCooldown()
    }
  } finally {
    submitting.value = false
  }
}

function startCooldown() {
  cooldown.value = 60
  const timer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0) {
      clearInterval(timer)
    }
  }, 1000)
}
</script>
