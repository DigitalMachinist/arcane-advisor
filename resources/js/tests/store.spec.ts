import { describe, expect, it, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useConsultStore } from '@/stores/consult';

describe('consult store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('instantiates with empty prompt and mutates state', () => {
        const store = useConsultStore();

        expect(store.prompt).toBe('');

        store.setPrompt('fireball!');

        expect(store.prompt).toBe('fireball!');
    });
});
