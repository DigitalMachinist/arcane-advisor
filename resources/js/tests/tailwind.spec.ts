import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

describe('tailwind utility classes', () => {
    it('renders a Tailwind utility class on the mounted component', () => {
        const TailwindProbe = defineComponent({
            render: () => h('p', { class: 'text-center font-bold' }, 'utility probe'),
        });

        const wrapper = mount(TailwindProbe);

        expect(wrapper.classes()).toContain('text-center');
        expect(wrapper.classes()).toContain('font-bold');
    });
});
