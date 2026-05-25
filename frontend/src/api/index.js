import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// 请求拦截器：自动附加 Token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  console.debug(`[API] ${config.method?.toUpperCase()} ${config.baseURL}${config.url}`)
  return config
})

// 响应拦截器：统一错误处理
api.interceptors.response.use(
  (response) => {
    console.debug(`[API] ${response.status} ${response.config.url}`)
    return response.data
  },
  (error) => {
    if (error.response) {
      const { status, data } = error.response
      const url = error.config?.url || ''

      console.debug(`[API] ERROR ${status} ${url}:`, data?.message || '')

      // 401 未认证处理
      if (status === 401 && !url.includes('/auth/login')) {
        // /auth/me 不做硬跳转，交由 auth store 处理
        if (url.includes('/auth/me')) {
          return Promise.reject(new Error('401: ' + (data?.message || '登录已过期')))
        }
        // 其他接口 401: 只有 localStorage 没有 token 时才硬跳转（真·未登录）
        // 有 token 但服务器返回 401 → 可能是 FastCGI 下 Authorization 头传递问题
        if (!localStorage.getItem('token')) {
          window.location.href = '/login'
        }
        return Promise.reject(new Error(data?.message || '登录已过期'))
      }

      return Promise.reject(new Error(data?.message || '请求失败'))
    }

    if (error.code === 'ECONNABORTED') {
      return Promise.reject(new Error('请求超时，请检查网络'))
    }

    return Promise.reject(new Error('网络异常，请稍后重试'))
  }
)

export default api
