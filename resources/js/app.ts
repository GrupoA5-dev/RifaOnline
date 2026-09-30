import '../css/app.css';
import axios from 'axios';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
if (csrf) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
}

const appName = document.querySelector<HTMLMetaElement>('meta[name="application-name"]')?.content || 'A5 Rifas';

createInertiaApp({
    title: (title) => title ? `${title} — ${appName}` : appName,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue'),
    ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#22c55e',
        showSpinner: false,
    },
});
