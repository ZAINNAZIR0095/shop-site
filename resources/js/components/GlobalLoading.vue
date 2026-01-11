<template>
  <!-- Your loading spinner or overlay -->
  <div v-if="loading" class="global-loading-overlay">
      <v-progress-circular
        indeterminate
        size="80"
        width="4"
        color="primary"
      ></v-progress-circular>
        </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Local reactive state
const loading = ref(true)

// Simulate initial load (remove or adjust delay as needed)
onMounted(() => {
  setTimeout(() => {
    loading.value = false
  }, 500)
})

// Expose the loading ref to parent components
defineExpose({
  loading
})
</script>

<style scoped>
.global-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  /* background: rgba(255, 255, 255, 0.8); */
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}
</style>
