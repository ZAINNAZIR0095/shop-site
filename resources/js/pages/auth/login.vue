    <template>
        <CCard class="login-card shadow" style="width: 100%; border-radius: 0px;">
        <!-- Card Header -->
        <CCardHeader class="text-center py-4 bg-primary text-white">
            <h2 class="mb-0">Inventory Management System</h2>
            <small class="opacity-75">Sign in to your account</small>
        </CCardHeader>

        <!-- Card Body -->
        <CCardBody class="p-4">
            <!-- Error Alert -->
            <transition name="fade">
            <CAlert v-if="errorMessage" color="danger" dismissible @close="errorMessage = ''">
                {{ errorMessage }}
            </CAlert>
            </transition>

            <!-- Success Alert (for password reset, etc.) -->
            <transition name="fade">
            <CAlert v-if="successMessage" color="success" dismissible @close="successMessage = ''">
                {{ successMessage }}
            </CAlert>
            </transition>

            <!-- Login Form -->
            <CForm @submit.prevent="handleLogin">
            <!-- Email/name Field -->
            <div class="mb-3">
                <CFormLabel for="name">name or Email</CFormLabel>
                <CFormInput
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Enter name or email"
                :invalid="errors.name"
                required
                />
                <CFormFeedback v-if="errors.name" invalid>
                {{ errors.name }}
                </CFormFeedback>
            </div>

            <!-- Password Field -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                <CFormLabel for="password">Password</CFormLabel>
                </div>
                <CInputGroup>
                <CFormInput
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Enter password"
                    :invalid="errors.password"
                    required
                />
                <CInputGroupText @click="showPassword = !showPassword" class="cursor-pointer">
                    <CIcon :icon="showPassword ? cilLockUnlocked : cilLockLocked" />
                </CInputGroupText>
                </CInputGroup>
                <CFormFeedback v-if="errors.password" invalid>
                {{ errors.password }}
                </CFormFeedback>
            </div>

            <!-- Remember Me Checkbox -->
            <div class="mb-3 form-check">
                <CFormCheck
                id="remember"
                v-model="form.remember"
                label="Remember me"
                />
            </div>

            <!-- Submit Button -->
            <div class="d-grid gap-2">
                <CButton
                type="submit"
                color="primary"
                :disabled="loading"
                class="py-2"
                >
                <CSpinner v-if="loading" size="sm" class="me-2" />
                {{ loading ? 'Signing in...' : 'Sign In' }}
                </CButton>

                <!-- Register Link (if enabled) -->
                <div v-if="showRegister" class="text-center mt-3">
                <span class="text-muted">Don't have an account? </span>
                <router-link :to="{ name: 'register' }" class="text-decoration-none">
                    Register
                </router-link>
                </div>
            </div>
            </CForm>
        </CCardBody>

        <!-- Card Footer (Optional) -->
        <CCardFooter class="text-center py-3 bg-light">
            <small class="text-muted">
            &copy; {{ currentYear }} Inventory Management System. All rights reserved.
            </small>
        </CCardFooter>
        </CCard>
    </template>

    <script setup>
    import { ref, reactive, onMounted } from 'vue'
    import { useRouter } from 'vue-router'
    import { useAuthStore } from '@/stores/authStore'
    import axios from 'axios'

    // CoreUI Icons
import { cilLockLocked, cilLockUnlocked } from '@coreui/icons'
    import { CIcon } from '@coreui/icons-vue'

    const router = useRouter()
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const showPassword = ref(false)
    const errorMessage = ref('')
    const successMessage = ref('')
    const currentYear = ref(new Date().getFullYear())

    // Form data
    const form = reactive({
    name: '',
    password: '',
    remember: false
    })

    // Form errors
    const errors = reactive({
    name: '',
    password: ''
    })

    // Configuration
    const showForgotPassword = ref(true) // Set to false to hide forgot password
    const showRegister = ref(false) // Set to true to show register link

    // Handle login submission
    const handleLogin = async () => {
    // Reset errors
    errors.name = ''
    errors.password = ''
    errorMessage.value = ''

    // Validation
    if (!form.name.trim()) {
        errors.name = 'name or email is required'
        return
    }

    if (!form.password) {
        errors.password = 'Password is required'
        return
    }

    loading.value = true

    try {
        // Call login
        const response = await axios.post('/login', {
        name: form.name,
        password: form.password,
        remember: form.remember
        })

        if (response.data.success) {
        // Store token and user data
        authStore.setToken(response.data.token)
        authStore.setUser(response.data.user)

        // Redirect to dashboard
        router.push('/')

        // Show success message
        successMessage.value = 'Login successful! Redirecting...'
        } else {
        errorMessage.value = response.data.message || 'Login failed'
        }
    } catch (error) {
        console.error('Login error:', error)

        // Handle different error types
        if (error.response) {
        // Server responded with error
        const { status, data } = error.response

        if (status === 422) {
            // Validation errors
            if (data.errors) {
            Object.keys(data.errors).forEach(key => {
                if (key === 'name' || key === 'email') errors.name = data.errors[key][0]
                if (key === 'password') errors.password = data.errors[key][0]
            })
            } else {
            errorMessage.value = data.message || 'Validation error'
            }
        } else if (status === 401) {
            errorMessage.value = 'Invalid credentials. Please check your name and password.'
        } else if (status === 403) {
            errorMessage.value = 'Your account is not active or has been suspended.'
        } else if (status === 429) {
            errorMessage.value = 'Too many login attempts. Please try again later.'
        } else {
            errorMessage.value = data.message || 'Login failed. Please try again.'
        }
        } else if (error.request) {
        // No response received
        errorMessage.value = 'Network error. Please check your connection.'
        } else {
        // Something else happened
        errorMessage.value = 'An unexpected error occurred.'
        }
    } finally {
        loading.value = false
    }
    }

    // Check if already logged in
    onMounted(() => {
    if (authStore.isAuthenticated) {
        router.push('/dashboard')
    }

    // Check for success messages (e.g., after password reset)
    const query = router.currentRoute.value.query
    if (query.success) {
        successMessage.value = query.success
    }

    // Auto-fill name if stored
    const savedname = localStorage.getItem('remembered_name')
    if (savedname) {
        form.name = savedname
        form.remember = true
    }
    })
    </script>

    <style scoped>
        .card-header{
            border-radius: 0px !important;
        }

        .login-card {
        --cui-card-border-radius: 0;
    border: none;
    border-radius: 0px;
    overflow: hidden;
    }

    .cursor-pointer {
    cursor: pointer;
    }

    .fade-enter-active,
    .fade-leave-active {
    transition: opacity 0.3s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
    opacity: 0;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
    .login-card {
        margin: 1rem;
    }
    }

    /* Animation for login button */
    @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
    }

    .btn-primary:not(:disabled):hover {
    animation: pulse 0.3s ease;
    }
    </style>
