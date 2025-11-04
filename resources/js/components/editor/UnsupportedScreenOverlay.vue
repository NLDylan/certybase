<template>
  <div class="unsupported-screen-overlay">
    <div class="overlay-content">
      <h2 class="overlay-title">Screen Too Small</h2>
      <p class="overlay-message">
        This application works best on larger screens. Please switch to a desktop or a larger tablet for the optimal experience.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts"></script>

<style scoped>
.unsupported-screen-overlay {
  display: none; /* Hidden by default */
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.9); /* Dark overlay with transparency */
  z-index: 9999; /* Ensure it's on top of everything */
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 15px;
  box-sizing: border-box;
}

.overlay-content {
  background-color: #222; /* Dark background for the content box */
  border: 1px solid #444; /* Darker border */
  border-radius: 8px;
  padding: 20px;
  max-width: 350px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* More pronounced shadow */
}

.overlay-title {
  font-size: 1.5rem;
  font-weight: 600; /* Semi-bold */
  color: #eee;
  margin-bottom: 10px;
}

.overlay-message {
  font-size: 1rem;
  color: #bbb;
  line-height: 1.5;
}

/* Show overlay only on screens smaller than md (768px) */
@media (max-width: 767px) {
  .unsupported-screen-overlay {
    display: flex; /* Show on small screens */
  }
}
</style>
