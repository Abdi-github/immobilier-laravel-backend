/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        unknown
    >;
    export default component;
}

// Ziggy route helper (injected globally via @routes blade directive + ziggy-js)
declare function route(name: string, params?: Record<string, unknown> | number | string, absolute?: boolean): string;
declare function route(): { current: (name: string) => boolean };

interface Window {
    route: typeof route;
}
