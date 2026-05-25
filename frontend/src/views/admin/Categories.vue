<template>
  <div class="flex">
    <AdminSidebar />
    <div class="flex-1 p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">📁 分类管理</h1>
        <button @click="startCreate" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">+ 新建分类</button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">名称</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">图标</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">题目数</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">排序</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">操作</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-500">#{{ cat.id }}</td>
              <td class="px-4 py-3 text-sm font-medium">{{ cat.name }}</td>
              <td class="px-4 py-3 text-sm">{{ cat.icon }}</td>
              <td class="px-4 py-3 text-center text-sm">{{ cat.challenge_count }}</td>
              <td class="px-4 py-3 text-center text-sm">{{ cat.sort_order }}</td>
              <td class="px-4 py-3 text-right">
                <button @click="startEdit(cat)" class="text-primary-600 hover:text-primary-700 text-sm mr-2">编辑</button>
                <button @click="deleteCategory(cat.id)" class="text-red-500 hover:text-red-600 text-sm">删除</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 表单弹窗 -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" @click.self="showForm = false">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
          <h2 class="text-xl font-bold mb-4">{{ editingId ? '编辑分类' : '新建分类' }}</h2>
          <form @submit.prevent="save" class="space-y-4">
            <input v-model="form.name" placeholder="分类名称" required class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <input v-model="form.icon" placeholder="图标（如 🌐）" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <input v-model="form.sort_order" type="number" placeholder="排序权重" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" />
            <div class="flex justify-end gap-3">
              <button type="button" @click="showForm = false" class="px-4 py-2 border rounded-lg text-sm">取消</button>
              <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium">{{ editingId ? '更新' : '创建' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const categories = ref([])
const showForm = ref(false)
const editingId = ref(null)
const form = ref({ name: '', icon: '', sort_order: 0 })

async function fetchCategories() {
  const res = await api.get('/admin/categories')
  categories.value = res.data || []
}

function startCreate() { editingId.value = null; form.value = { name: '', icon: '', sort_order: 0 }; showForm.value = true }
function startEdit(cat) { editingId.value = cat.id; form.value = { name: cat.name, icon: cat.icon, sort_order: cat.sort_order }; showForm.value = true }

async function save() {
  try {
    if (editingId.value) { await api.put(`/admin/categories?id=${editingId.value}`, form.value) }
    else { await api.post('/admin/categories', form.value) }
    showForm.value = false; fetchCategories()
  } catch (e) { alert(e.message) }
}

async function deleteCategory(id) {
  if (!confirm('确定删除？')) return
  try { await api.delete(`/admin/categories?id=${id}`); fetchCategories() }
  catch (e) { alert(e.message) }
}

onMounted(fetchCategories)
</script>
