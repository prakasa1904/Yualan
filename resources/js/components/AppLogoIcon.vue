<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <h2 style="text-align:center; width: 200px; color: white; font-weight: bold; font-size: 28px;">
        {{ appName }}
    </h2>
</template>
