<script setup lang="ts">
const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex items-center justify-center w-full">
        <div class="px-2 py-1 rounded-md bg-white dark:bg-gray-900 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm max-w-[180px] w-full mx-auto text-center">
            <span :title="appName" class="block truncate select-none font-semibold text-sm text-gray-900 dark:text-gray-100">{{ appName }}</span>
        </div>
    </div>
</template>
