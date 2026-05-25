import { defineStore } from 'pinia'
import api from '@/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    username: (state) => state.user?.username || '',
  },

  actions: {
    async init() {
      const token = localStorage.getItem('token')
      const userStr = localStorage.getItem('user')
      if (token && userStr) {
        try {
          this.token = token
          this.user = JSON.parse(userStr)
          await this.fetchMe()
        } catch {
          this.logout()
        }
      }
    },

    async login(username, password) {
      this.loading = true
      try {
        const res = await api.post('/auth/login', { username, password })

        // 验证登录响应结构
        if (!res || !res.data || !res.data.token) {
          console.error('[Auth] 登录响应缺少 token:', res)
          throw new Error('登录响应异常，请检查后端接口')
        }

        this.token = res.data.token
        this.user = res.data.user
        localStorage.setItem('token', res.data.token)
        localStorage.setItem('user', JSON.stringify(res.data.user))

        console.log('[Auth] 登录成功, token:', res.data.token.substring(0, 30) + '...')
        return res
      } catch (e) {
        this.loading = false
        throw e
      } finally {
        this.loading = false
      }
    },

    async register(username, email, password) {
      this.loading = true
      try {
        return await api.post('/auth/register', { username, email, password })
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      try {
        const res = await api.get('/auth/me')
        // res = 经过拦截器处理后的响应体
        // res.data = 用户数据对象
        this.user = res.data
        localStorage.setItem('user', JSON.stringify(res.data))
        return res.data
      } catch (e) {
        console.error('[Auth] fetchMe 失败:', e.message)
        // 判断是否为真正的 401 认证失败（错误消息以 "401:" 开头）
        if (e.message && e.message.startsWith('401:')) {
          // 服务器明确拒绝认证 → 清空登录态
          this.logout()
        }
        // 其他错误（网络故障、500）不销毁登录态
        throw e
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch {
        // ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      console.log('[Auth] 已退出登录')
    },
  },
})
