import type { RouteRecordRaw } from 'vue-router';
import Landing from '@/pages/Landing.vue';

export const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'landing',
        component: Landing,
    },
];
