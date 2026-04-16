import { defineStore } from 'pinia';

interface ConsultState {
    prompt: string;
}

export const useConsultStore = defineStore('consult', {
    state: (): ConsultState => ({
        prompt: '',
    }),
    actions: {
        setPrompt(value: string): void {
            this.prompt = value;
        },
    },
});
