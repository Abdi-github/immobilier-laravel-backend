/// <reference types="vitest/globals" />

import { config } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import PrimeVue from 'primevue/config';

// Set up Pinia for all tests
beforeEach(() => {
    setActivePinia(createPinia());
});

// Global PrimeVue stub
config.global.plugins = [PrimeVue];

// Stub Inertia components
config.global.stubs = {
    Head: true,
    Link: {
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
};
