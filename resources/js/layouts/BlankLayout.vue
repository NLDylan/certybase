<script setup lang="ts">
// Intentionally empty: this layout renders a full-page area with no header/footer
</script>

<template>
    <div class="h-dvh w-full bg-background">
        <div class="mx-auto flex h-dvh w-full max-w-7xl items-center justify-center p-6">
            <slot />
        </div>
    </div>
</template>


