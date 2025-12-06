import type { SharedProps } from './models';

declare module '@inertiajs/vue3' {
    interface PageProps extends SharedProps {}
}
