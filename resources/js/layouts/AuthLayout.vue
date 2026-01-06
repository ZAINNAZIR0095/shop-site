<template>
  <div class="auth-container">
    <!-- Left Image Section -->
    <div class="auth-image"></div>

    <!-- Right Login Section - Remove all padding/margin -->
    <div class="auth-card">
      <router-view />
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  margin: 0;
  padding: 0;
}

/* Left Image Section - Full height, no gaps */
.auth-image {
  flex: 0 0 65%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  margin: 0;
  padding: 0;
}

/* Right Login Section - Full height, no gaps */
.auth-card {

  background: white;
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100vh; /* Full viewport height */
}

/* Remove any default margins from router-view content */
.auth-card > * {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

@media (max-width: 768px) {
  .auth-container {
    flex-direction: column;
  }

  .auth-image {
    display: none;
  }

  .auth-card {
    flex: 0 0 100%;
    width: 100vw;
    height: 100vh;
  }
}
</style>
