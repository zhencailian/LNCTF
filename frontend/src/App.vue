<template>
  <div class="min-h-screen bg-gray-50">
    <NavBar />
    <main class="max-w-7xl mx-auto px-4 py-6">
      <router-view />
    </main>
    <footer class="text-center text-gray-400 text-sm py-6 border-t">
      <p>LNCTF 训练平台 v2.0 &copy; 2026 | 仅供学习交流使用</p>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import NavBar from '@/components/NavBar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

onMounted(() => {
  auth.init()
})
</script>
