<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const app = computed(() => page.props.app as any);
const myNumbersHref = ref('');

onMounted(() => {
    const base = app.value?.urls?.my_numbers || '/meus-numeros';
    const token = window.localStorage.getItem('a5_customer_access_token');
    myNumbersHref.value = token ? `${String(base).replace(/\/$/, '')}/${token}` : base;
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-white/8 bg-[#0c1418]/92 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <Link :href="app?.urls?.home || '/'" class="flex min-w-0 items-center gap-3 text-white">
                <img
                    v-if="app?.branding?.logo_url"
                    :src="app.branding.logo_url"
                    :alt="app?.name || 'Logo'"
                    class="max-w-[320px] object-contain object-left"
                    :style="{ height: `${Number(app?.branding?.logo_size || 44)}px` }"
                />
                <span v-else class="a5-brand-bg grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-sm font-black shadow-lg">A5</span>
                <div v-if="!app?.branding?.logo_url" class="min-w-0 leading-tight">
                    <div class="truncate font-black tracking-tight">{{ app?.name || 'A5 Rifas' }}</div>
                    <div class="a5-brand-text truncate text-[10px] font-bold uppercase tracking-[.2em]">{{ app?.branding?.tagline || 'Campanhas digitais' }}</div>
                </div>
            </Link>

            <div class="flex items-center gap-2">
                <a
                    v-if="app?.contact?.whatsapp"
                    :href="`https://wa.me/${String(app.contact.whatsapp).replace(/\D/g, '')}`"
                    target="_blank"
                    rel="noopener"
                    class="hidden rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10 sm:block"
                >
                    Suporte
                </a>
                <a
                    v-if="myNumbersHref"
                    :href="myNumbersHref"
                    class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10"
                >
                    Meus números
                </a>
                <a :href="app?.urls?.admin" class="a5-brand-bg rounded-xl px-3 py-2 text-xs font-black transition">
                    Gestão
                </a>
            </div>
        </div>
    </header>
</template>
