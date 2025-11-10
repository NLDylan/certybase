<script setup lang="ts">
// Intentionally empty: this layout renders a full-page area with no header/footer
</script>

<template>
    <div class="min-h-dvh w-full bg-background">
        <div class="mx-auto flex w-full max-w-7xl flex-col items-center px-6 py-12">
            <slot />
        </div>
    </div>
</template>


