import { defineStore } from 'pinia'
import api from '@/api'

export const useLeaderboardStore = defineStore('leaderboard', {
  state: () => ({
    users: [],
    teams: [],
    activeTab: 'users',
    pagination: {
      page: 1,
      perPage: 50,
      total: 0,
    },
    loading: false,
  }),

  actions: {
    async fetchLeaderboard(page = 1) {
      this.loading = true
      try {
        const endpoint = this.activeTab === 'users' ? '/leaderboard/index' : '/leaderboard/teams'
        const res = await api.get(endpoint, {
          params: { page, per_page: this.pagination.perPage },
        })
        if (this.activeTab === 'users') {
          this.users = res.data.items
        } else {
          this.teams = res.data.items
        }
        this.pagination = {
          page: res.data.page,
          perPage: res.data.per_page,
          total: res.data.total,
        }
      } catch (e) {
        console.error('获取排行榜失败:', e)
      } finally {
        this.loading = false
      }
    },

    switchTab(tab) {
      this.activeTab = tab
      this.pagination.page = 1
      this.fetchLeaderboard()
    },
  },
})
