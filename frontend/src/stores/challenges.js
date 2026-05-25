import { defineStore } from 'pinia'
import api from '@/api'

export const useChallengeStore = defineStore('challenges', {
  state: () => ({
    challenges: [],
    categories: [],
    currentChallenge: null,
    filters: {
      category: '',
      difficulty: '',
      status: '',
      keyword: '',
      tag: '',
    },
    pagination: {
      page: 1,
      perPage: 20,
      total: 0,
    },
    loading: false,
  }),

  getters: {
    filteredChallenges: (state) => state.challenges,
  },

  actions: {
    async fetchCategories() {
      try {
        const res = await api.get('/challenges/categories')
        this.categories = res.data
      } catch (e) {
        console.error('获取分类失败:', e)
      }
    },

    async fetchChallenges(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: this.pagination.perPage,
          ...this.filters,
        }
        // 清除空值
        Object.keys(params).forEach(k => {
          if (!params[k]) delete params[k]
        })

        const res = await api.get('/challenges/list', { params })
        this.challenges = res.data.items
        this.pagination = {
          page: res.data.page,
          perPage: res.data.per_page,
          total: res.data.total,
        }
      } catch (e) {
        console.error('获取题目列表失败:', e)
        this.challenges = []
      } finally {
        this.loading = false
      }
    },

    async fetchChallengeDetail(id) {
      this.loading = true
      try {
        const res = await api.get(`/challenges/detail/${id}`)
        this.currentChallenge = res.data
        return res.data
      } catch (e) {
        throw e
      } finally {
        this.loading = false
      }
    },

    setFilter(key, value) {
      this.filters[key] = value
      this.pagination.page = 1
    },

    clearFilters() {
      this.filters = { category: '', difficulty: '', status: '', keyword: '', tag: '' }
      this.pagination.page = 1
    },
  },
})
