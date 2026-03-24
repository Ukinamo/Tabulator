import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import AppWithToasts from '@/components/AppWithToasts.vue';
import '../css/app.css';
import '@vuepic/vue-datepicker/dist/main.css';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(AppWithToasts, null, () => h(App, props)),
        });
        app.use(plugin);
        app.mount(el);
    },
    progress: {
        color: '#b40066',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
