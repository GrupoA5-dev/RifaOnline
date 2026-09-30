<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppHeader from '../Components/AppHeader.vue';

const page = usePage();
const app = computed(() => page.props.app as any);
const brandingStyle = computed(() => ({
    '--a5-primary': app.value?.branding?.primary_color || '#22c55e',
    '--a5-primary-contrast': app.value?.branding?.button_text_color || '#07110b',
}));
</script>

<template>
    <div class="min-h-screen bg-[#0b1216] text-white" :style="brandingStyle">
        <AppHeader />
        <main><slot /></main>
        <footer class="mt-20 border-t border-white/8 bg-[#091014]">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 py-10 text-sm text-slate-400 sm:px-6 md:grid-cols-2">
                <div>
                    <div class="font-black text-white">{{ app?.name || 'A5 Rifas' }}</div>
                    <p class="mt-2 max-w-md leading-6">{{ app?.branding?.tagline || 'Campanhas digitais' }}</p>
                </div>
                <div class="md:text-right">
                    <p>
                        © {{ app?.footer?.year || new Date().getFullYear() }}
                        {{ app?.footer?.owner || app?.name || 'A5 Rifas' }}.
                        {{ app?.footer?.rights || 'Todos os direitos reservados.' }}
                    </p>
                    <p v-if="app?.footer?.developer_name" class="mt-2">
                        Desenvolvido por
                        <a
                            v-if="app?.footer?.developer_url"
                            :href="app.footer.developer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-semibold text-white hover:underline"
                        >{{ app.footer.developer_name }}</a>
                        <span v-else class="font-semibold text-white">{{ app.footer.developer_name }}</span>
                    </p>
                    <p v-if="app?.contact?.email" class="mt-2">{{ app.contact.email }}</p>
                    <p class="mt-2 text-xs text-slate-600">Versão {{ app?.version || '0.10.0' }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
