import 'vue';

declare module 'vue' {
    interface ComponentCustomProperties {
        route(name: string, params?: Record<string, unknown> | number | string, absolute?: boolean): string;
    }
}
