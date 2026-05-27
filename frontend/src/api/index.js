import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add auth token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 responses (token expired)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth API
export const authApi = {
  login: (email, password) => api.post('/login', { email, password }),
  logout: () => api.post('/logout'),
  getUser: () => api.get('/user'),
};

// Dashboard API
export const dashboardApi = {
  getStats: () => api.get('/dashboard'),
};

// Users API
export const usersApi = {
  getAll: () => api.get('/users'),
  create: (data) => api.post('/users', data),
  update: (id, data) => api.put(`/users/${id}`, data),
  delete: (id) => api.delete(`/users/${id}`),
};

// Cupboards API
export const cupboardsApi = {
  getAll: () => api.get('/cupboards'),
  getOne: (id) => api.get(`/cupboards/${id}`),
  create: (data) => api.post('/cupboards', data),
  update: (id, data) => api.put(`/cupboards/${id}`, data),
  delete: (id) => api.delete(`/cupboards/${id}`),
};

// Places API
export const placesApi = {
  getAll: (cupboardId = null) => api.get('/places', { params: cupboardId ? { cupboard_id: cupboardId } : {} }),
  getOne: (id) => api.get(`/places/${id}`),
  create: (data) => api.post('/places', data),
  update: (id, data) => api.put(`/places/${id}`, data),
  delete: (id) => api.delete(`/places/${id}`),
};

// Items API
export const itemsApi = {
  getAll: (params = {}) => api.get('/items', { params }),
  getOne: (id) => api.get(`/items/${id}`),
  create: (formData) => api.post('/items', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  update: (id, formData) => api.post(`/items/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    params: { _method: 'PUT' },
  }),
  delete: (id) => api.delete(`/items/${id}`),
  updateQuantity: (id, action, amount) => api.patch(`/items/${id}/quantity`, { action, amount }),
  updateStatus: (id, status) => api.patch(`/items/${id}/status`, { status }),
};

// Borrowings API
export const borrowingsApi = {
  getAll: (params = {}) => api.get('/borrowings', { params }),
  getOne: (id) => api.get(`/borrowings/${id}`),
  create: (data) => api.post('/borrowings', data),
  returnItem: (id) => api.patch(`/borrowings/${id}/return`),
};

// Audit Logs API
export const auditLogsApi = {
  getAll: (params = {}) => api.get('/audit-logs', { params }),
};

export default api;
