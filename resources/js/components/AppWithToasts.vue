<script setup lang="ts">
import ToastContainer from '@/components/ui/ToastContainer.vue';
</script>

<template>
    <div class="min-h-screen">
        <slot />
        <ToastContainer />
    </div>
</template>
