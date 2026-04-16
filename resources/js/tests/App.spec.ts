import { describe, expect, it, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createRouter, createMemoryHistory } from 'vue-router';
import App from '@/App.vue';
import { routes } from '@/router';

describe('App', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('mounts with router and renders the landing route', async () => {
        const router = createRouter({
            history: createMemoryHistory(),
            routes,
        });

        router.push('/');
        await router.isReady();

        const wrapper = mount(App, {
            global: {
                plugins: [router, createPinia()],
            },
        });

        await flushPromises();

        expect(wrapper.html()).toContain('Arcane Advisor');
    });
});
