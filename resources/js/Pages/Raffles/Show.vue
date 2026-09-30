<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import PublicLayout from '../../Layouts/PublicLayout.vue';

const props = defineProps<{ raffle: any }>();
const showCheckout = ref(false);
const manualMode = computed(() => props.raffle.allocation_mode === 'manual');
const checkoutFields = computed(() => props.raffle.checkout_fields ?? {
    name: true,
    email: true,
    phone: true,
    document: true,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    document: '',
    quantity: props.raffle.min_purchase || 1,
    numbers: [] as number[],
    terms: false,
});

const numbersPage = ref(1);
const numbersLastPage = ref(1);
const numberOptions = ref<Array<{ value: number; label: string; available: boolean }>>([]);
const loadingNumbers = ref(false);
const numbersError = ref('');
const shareCopied = ref(false);

const money = (cents: number) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((cents || 0) / 100);
const selectedQuantity = computed(() => manualMode.value ? form.numbers.length : Math.max(0, Number(form.quantity) || 0));
const total = computed(() => props.raffle.price_cents * selectedQuantity.value);
const quick = computed(() => {
    const min = props.raffle.min_purchase || 1;
    const options = [min, Math.max(min, 5), Math.max(min, 10), Math.max(min, 50), Math.max(min, 100)];
    return [...new Set(options)].filter((n) => !props.raffle.max_purchase || n <= props.raffle.max_purchase);
});
const canContinue = computed(() => {
    if (!manualMode.value) return selectedQuantity.value >= (props.raffle.min_purchase || 1);
    if (selectedQuantity.value < (props.raffle.min_purchase || 1)) return false;
    if (props.raffle.max_purchase && selectedQuantity.value > props.raffle.max_purchase) return false;
    return true;
});

function setQuantity(value: number) {
    form.quantity = value;
}

function add(value: number) {
    const max = props.raffle.max_purchase ?? Number.MAX_SAFE_INTEGER;
    form.quantity = Math.min(max, Math.max(props.raffle.min_purchase, Number(form.quantity || 0) + value));
}

function isSelected(number: number) {
    return form.numbers.includes(number);
}

function toggleNumber(number: number) {
    if (isSelected(number)) {
        form.numbers = form.numbers.filter((value) => value !== number);
    } else {
        const max = props.raffle.max_purchase ?? Number.MAX_SAFE_INTEGER;
        if (form.numbers.length >= max) {
            form.setError('numbers', `Você pode escolher no máximo ${max} número(s).`);
            return;
        }
        form.clearErrors('numbers');
        form.numbers = [...form.numbers, number].sort((a, b) => a - b);
    }
    form.quantity = form.numbers.length;
}

async function loadNumbers(page = 1) {
    if (!manualMode.value) return;

    loadingNumbers.value = true;
    numbersError.value = '';

    try {
        const url = new URL(props.raffle.numbers_url, window.location.origin);
        url.searchParams.set('page', String(page));
        url.searchParams.set('per_page', '100');

        const response = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Não foi possível carregar os números.');

        const data = await response.json();
        numberOptions.value = data.numbers ?? [];
        numbersPage.value = Number(data.page || 1);
        numbersLastPage.value = Number(data.last_page || 1);
    } catch (error) {
        numbersError.value = error instanceof Error ? error.message : 'Não foi possível carregar os números.';
    } finally {
        loadingNumbers.value = false;
    }
}

function reserve() {
    if (manualMode.value) {
        form.quantity = form.numbers.length;
    }

    form.post(props.raffle.reserve_url, {
        preserveScroll: true,
        onError: () => {
            if (manualMode.value && form.errors.numbers) loadNumbers(numbersPage.value);
        },
    });
}

const shareUrl = computed(() => props.raffle.share_url || (typeof window !== 'undefined' ? window.location.href : ''));
const shareText = computed(() => `Confira a campanha ${props.raffle.title}`);

function track(event: string) {
    if (!props.raffle.analytics_url) return;
    axios.post(props.raffle.analytics_url, { event }).catch(() => undefined);
}

function openShare(url: string) {
    window.open(url, '_blank', 'noopener,noreferrer,width=720,height=640');
}

function shareWhatsApp() {
    track('whatsapp_share');
    openShare(`https://wa.me/?text=${encodeURIComponent(`${shareText.value} ${shareUrl.value}`)}`);
}

async function shareNative() {
    track('share_click');

    if (navigator.share) {
        try {
            await navigator.share({ title: props.raffle.title, text: shareText.value, url: shareUrl.value });
            return;
        } catch {
            return;
        }
    }

    await copyShare(false);
}

async function copyShare(shouldTrack = true) {
    if (shouldTrack) track('copy_click');

    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(shareUrl.value);
    } else {
        const input = document.createElement('textarea');
        input.value = shareUrl.value;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }

    shareCopied.value = true;
    window.setTimeout(() => shareCopied.value = false, 1800);
}

function openCampaignContact(type: 'whatsapp' | 'instagram') {
    if (type === 'whatsapp' && props.raffle.whatsapp_url) {
        track('whatsapp_contact');
        window.open(props.raffle.whatsapp_url, '_blank', 'noopener,noreferrer');
    }

    if (type === 'instagram' && props.raffle.instagram_url) {
        track('instagram_contact');
        window.open(props.raffle.instagram_url, '_blank', 'noopener,noreferrer');
    }
}

function openCheckout() {
    track('purchase_click');
    showCheckout.value = true;
}

onMounted(() => {
    if (manualMode.value) loadNumbers(1);
});
</script>

<template>
    <Head :title="raffle.seo?.page_title || raffle.title">
        <meta v-if="raffle.seo?.description" head-key="description" name="description" :content="raffle.seo.description" />
        <meta v-if="raffle.seo?.keywords" head-key="keywords" name="keywords" :content="raffle.seo.keywords" />
        <meta head-key="og:title" property="og:title" :content="raffle.seo?.title || raffle.title" />
        <meta v-if="raffle.seo?.description" head-key="og:description" property="og:description" :content="raffle.seo.description" />
        <meta head-key="og:url" property="og:url" :content="raffle.seo?.url || raffle.share_url" />
        <meta v-if="raffle.seo?.image" head-key="og:image" property="og:image" :content="raffle.seo.image" />
        <meta head-key="twitter:card" name="twitter:card" :content="raffle.seo?.image ? 'summary_large_image' : 'summary'" />
    </Head>
    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10">
            <div class="grid gap-7 lg:grid-cols-[1.18fr_.82fr] lg:items-start">
                <div class="space-y-6">
                    <div class="overflow-hidden rounded-3xl border border-white/8 bg-[#122027]">
                        <div class="relative aspect-[16/9] overflow-hidden bg-gradient-to-br from-[#143329] to-[#101b20]">
                            <img v-if="raffle.cover_url" :src="raffle.cover_url" :alt="raffle.title" class="h-full w-full object-cover" />
                            <div v-else class="absolute inset-0 grid place-items-center text-7xl font-black text-white/8">A5</div>

                            <div class="absolute right-3 top-3 z-10 flex items-center gap-2 sm:right-4 sm:top-4">
                                <button
                                    type="button"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-black/45 text-white shadow-lg backdrop-blur-md transition hover:bg-black/70"
                                    :title="shareCopied ? 'Link copiado' : 'Copiar link'"
                                    :aria-label="shareCopied ? 'Link copiado' : 'Copiar link'"
                                    @click="copyShare()"
                                >
                                    <svg v-if="!shareCopied" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <svg v-else viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 4 4L19 6"/></svg>
                                </button>
                                <button
                                    type="button"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-black/45 text-white shadow-lg backdrop-blur-md transition hover:bg-black/70"
                                    title="Compartilhar"
                                    aria-label="Compartilhar"
                                    @click="shareNative"
                                >
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4"/><path d="m8.6 13.5 6.8 4"/></svg>
                                </button>
                                <button
                                    type="button"
                                    class="grid h-10 w-10 place-items-center rounded-full border border-white/15 bg-black/45 text-white shadow-lg backdrop-blur-md transition hover:bg-black/70"
                                    title="Compartilhar no WhatsApp"
                                    aria-label="Compartilhar no WhatsApp"
                                    @click="shareWhatsApp"
                                >
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7a11.7 11.7 0 0 0 5.4 1.4h.1c6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.1-3.5-8.4Zm-8.3 18.2h-.1a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.5 4.7Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.5-1.5a9.2 9.2 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.3 3.4 1.5 3.6c.2.2 2.5 3.8 6 5.3.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4Z"/></svg>
                                </button>
                            </div>

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-5 pt-20">
                                <span class="rounded-full a5-brand-bg px-3 py-1 text-xs font-black">{{ money(raffle.price_cents) }} cada número</span>
                            </div>
                        </div>
                        <div class="p-5 sm:p-7">
                            <h1 class="text-2xl font-black tracking-tight sm:text-4xl">{{ raffle.title }}</h1>
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-400">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span v-if="raffle.organizer">Organizado por <b class="text-white">{{ raffle.organizer }}</b></span>
                                    <span v-if="raffle.draw_reference" class="rounded-full border border-white/10 px-3 py-1">{{ raffle.draw_reference }}</span>
                                </div>
                                <div v-if="raffle.instagram_url || raffle.whatsapp_url" class="flex items-center gap-2">
                                    <button v-if="raffle.instagram_url" type="button" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/10" title="Instagram da campanha" aria-label="Instagram da campanha" @click="openCampaignContact('instagram')">
                                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                                    </button>
                                    <button v-if="raffle.whatsapp_url" type="button" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/10" title="Falar no WhatsApp" aria-label="Falar no WhatsApp" @click="openCampaignContact('whatsapp')">
                                        <svg viewBox="0 0 24 24" class="h-4.5 w-4.5" fill="currentColor"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7a11.7 11.7 0 0 0 5.4 1.4h.1c6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.1-3.5-8.4Zm-8.3 18.2h-.1a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.5 4.7Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.5-1.5a9.2 9.2 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.3 3.4 1.5 3.6c.2.2 2.5 3.8 6 5.3.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4Z"/></svg>
                                    </button>
                                </div>
                            </div>


                            <div class="mt-6">
                                <div class="flex justify-between text-xs font-bold text-slate-400">
                                    <span>{{ raffle.paid_numbers.toLocaleString('pt-BR') }} vendidos</span>
                                    <span>{{ raffle.progress }}%</span>
                                </div>
                                <div class="mt-2 h-3 overflow-hidden rounded-full bg-white/7">
                                    <div class="h-full rounded-full a5-brand-progress" :style="{ width: `${raffle.progress}%` }"></div>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">{{ raffle.available_numbers.toLocaleString('pt-BR') }} números disponíveis no momento</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="raffle.prizes.length" class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-7">
                        <h2 class="text-lg font-black">Prêmios</h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div v-for="prize in raffle.prizes" :key="`${prize.position}-${prize.title}`" class="flex gap-4 rounded-2xl bg-white/[.035] p-4">
                                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl a5-brand-soft font-black a5-brand-text">{{ prize.position }}º</div>
                                <div>
                                    <div class="font-bold">{{ prize.title }}</div>
                                    <p v-if="prize.description" class="mt-1 text-sm leading-5 text-slate-400">{{ prize.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="raffle.winners?.length" class="rounded-3xl border a5-brand-border a5-brand-soft p-5 sm:p-7">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 place-items-center rounded-2xl a5-brand-bg text-2xl">🏆</div>
                            <div><p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Resultado oficial</p><h2 class="text-xl font-black">Ganhadores</h2></div>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div v-for="winner in raffle.winners" :key="`${winner.number}-${winner.name}`" class="rounded-2xl border border-white/8 bg-black/15 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div><div class="text-xs text-slate-500">{{ winner.prize || 'Ganhador' }}</div><div class="mt-1 text-lg font-black">{{ winner.name }}</div></div>
                                    <span class="rounded-xl a5-brand-bg px-3 py-2 font-mono text-sm font-black">{{ winner.number }}</span>
                                </div>
                                <div v-if="winner.announced_at" class="mt-3 text-xs text-slate-500">Publicado em {{ winner.announced_at }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="raffle.description || raffle.rules" class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-7">
                        <h2 class="text-lg font-black">Descrição / Regulamento</h2>
                        <div v-if="raffle.description" class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-300">{{ raffle.description }}</div>
                        <div v-if="raffle.rules" class="mt-5 border-t border-white/8 pt-5 whitespace-pre-line text-sm leading-7 text-slate-400">{{ raffle.rules }}</div>
                    </div>

                    <div v-if="raffle.ranking.length" class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-7">
                        <h2 class="text-lg font-black">Top colaboradores</h2>
                        <div class="mt-4 space-y-3">
                            <div v-for="(rank, index) in raffle.ranking" :key="rank.name" class="flex items-center justify-between rounded-2xl bg-white/[.035] px-4 py-3">
                                <div class="flex items-center gap-3"><span class="grid h-8 w-8 place-items-center rounded-full a5-brand-soft text-sm font-black a5-brand-text">{{ index + 1 }}</span><span class="font-bold">{{ rank.name }}</span></div>
                                <strong>{{ rank.tickets.toLocaleString('pt-BR') }} números</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="lg:sticky lg:top-24">
                    <div class="rounded-3xl border a5-brand-border bg-[#12221c] p-5 shadow-2xl shadow-black/20 sm:p-6">
                        <template v-if="raffle.purchasable">
                            <template v-if="!manualMode">
                                <p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Selecione a quantidade</p>
                                <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-3">
                                    <button v-for="q in quick" :key="q" type="button" class="rounded-xl border px-3 py-3 text-sm font-black transition" :class="Number(form.quantity) === q ? 'a5-brand-border a5-brand-bg' : 'border-white/10 bg-white/5 hover:bg-white/10'" @click="setQuantity(q)">+{{ q }}</button>
                                </div>
                                <div class="mt-4 flex items-center gap-2 rounded-2xl border border-white/10 bg-black/15 p-2">
                                    <button type="button" class="grid h-11 w-11 place-items-center rounded-xl bg-white/7 font-black hover:bg-white/12" @click="add(-1)">−</button>
                                    <input v-model.number="form.quantity" type="number" :min="raffle.min_purchase" :max="raffle.max_purchase || undefined" class="h-11 min-w-0 flex-1 bg-transparent text-center text-xl font-black outline-none" />
                                    <button type="button" class="grid h-11 w-11 place-items-center rounded-xl bg-white/7 font-black hover:bg-white/12" @click="add(1)">+</button>
                                </div>
                                <p v-if="form.errors.quantity" class="mt-2 text-sm font-semibold text-red-400">{{ form.errors.quantity }}</p>
                            </template>

                            <template v-else>
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Escolha seus números</p>
                                        <p class="mt-1 text-xs text-slate-500">Disponíveis em blocos de 100 para manter a página rápida.</p>
                                    </div>
                                    <span class="rounded-full a5-brand-soft px-3 py-1 text-xs font-black a5-brand-text">{{ form.numbers.length }} selecionado(s)</span>
                                </div>

                                <div v-if="loadingNumbers" class="mt-4 rounded-2xl bg-white/[.035] p-6 text-center text-sm text-slate-400">Carregando números...</div>
                                <div v-else-if="numbersError" class="mt-4 rounded-2xl border border-red-500/20 bg-red-500/5 p-4 text-sm text-red-300">
                                    {{ numbersError }}
                                    <button type="button" class="ml-2 font-black underline" @click="loadNumbers(numbersPage)">Tentar novamente</button>
                                </div>
                                <div v-else class="mt-4 grid grid-cols-5 gap-2 sm:grid-cols-10 lg:grid-cols-5">
                                    <button
                                        v-for="item in numberOptions"
                                        :key="item.value"
                                        type="button"
                                        :disabled="!item.available"
                                        class="rounded-xl border px-2 py-2 text-xs font-black transition"
                                        :class="isSelected(item.value)
                                            ? 'a5-brand-border a5-brand-bg'
                                            : item.available
                                                ? 'a5-number-available border-white/10 bg-white/5 text-white'
                                                : 'cursor-not-allowed border-white/5 bg-black/20 text-slate-700 line-through'"
                                        @click="item.available && toggleNumber(item.value)"
                                    >{{ item.label }}</button>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <button type="button" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-black disabled:opacity-30" :disabled="numbersPage <= 1 || loadingNumbers" @click="loadNumbers(numbersPage - 1)">← Anterior</button>
                                    <span class="text-xs text-slate-500">Página {{ numbersPage }} de {{ numbersLastPage }}</span>
                                    <button type="button" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-black disabled:opacity-30" :disabled="numbersPage >= numbersLastPage || loadingNumbers" @click="loadNumbers(numbersPage + 1)">Próxima →</button>
                                </div>

                                <p v-if="form.errors.numbers" class="mt-3 text-sm font-semibold text-red-400">{{ form.errors.numbers }}</p>
                                <p class="mt-3 text-xs leading-5 text-slate-500">Mínimo: {{ raffle.min_purchase }}<span v-if="raffle.max_purchase"> · Máximo: {{ raffle.max_purchase }}</span>. Números indisponíveis aparecem bloqueados.</p>
                            </template>

                            <div class="mt-5 flex items-center justify-between border-t border-white/8 pt-5">
                                <div><span class="text-sm text-slate-400">Total</span><div class="mt-1 text-xs text-slate-500">{{ selectedQuantity }} número(s)</div></div>
                                <strong class="text-2xl a5-brand-text">{{ money(total) }}</strong>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                A reserva dura {{ raffle.reservation_minutes }} minutos.
                                <span v-if="manualMode">O servidor confirma novamente a disponibilidade antes de reservar.</span>
                                <span v-else>Os números são definidos com segurança pelo servidor.</span>
                            </p>
                            <button type="button" :disabled="!canContinue" class="mt-5 w-full rounded-2xl a5-brand-bg px-5 py-4 text-sm font-black uppercase tracking-wide transition disabled:cursor-not-allowed disabled:opacity-40" @click="openCheckout">Participar agora</button>
                        </template>
                        <template v-else>
                            <div class="py-6 text-center">
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white/5 text-2xl">✓</div>
                                <h3 class="mt-4 text-xl font-black">{{ raffle.status_label }}</h3>
                                <p class="mt-2 text-sm text-slate-400">Esta campanha não está disponível para novas compras.</p>
                            </div>
                        </template>
                    </div>
                </aside>
            </div>
        </section>

        <div v-if="showCheckout" class="fixed inset-0 z-50 grid place-items-end bg-black/70 p-0 backdrop-blur-sm sm:place-items-center sm:p-5" @click.self="showCheckout = false">
            <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl border border-white/10 bg-[#101a1f] p-5 shadow-2xl sm:max-w-xl sm:rounded-3xl sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Finalizar reserva</p><h2 class="mt-2 text-2xl font-black">Seus dados</h2></div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl bg-white/7 text-xl" @click="showCheckout = false">×</button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="reserve">
                    <div v-if="checkoutFields.name"><label class="mb-1.5 block text-xs font-bold text-slate-400">Nome completo</label><input v-model="form.name" class="a5-input" autocomplete="name" /><p v-if="form.errors.name" class="a5-error">{{ form.errors.name }}</p></div>
                    <div v-if="checkoutFields.email"><label class="mb-1.5 block text-xs font-bold text-slate-400">E-mail</label><input v-model="form.email" type="email" class="a5-input" autocomplete="email" /><p v-if="form.errors.email" class="a5-error">{{ form.errors.email }}</p></div>
                    <div v-if="checkoutFields.phone || checkoutFields.document" class="grid gap-4 sm:grid-cols-2">
                        <div v-if="checkoutFields.phone"><label class="mb-1.5 block text-xs font-bold text-slate-400">WhatsApp com DDD</label><input v-model="form.phone" class="a5-input" inputmode="tel" autocomplete="tel" placeholder="(83) 99999-9999" /><p v-if="form.errors.phone" class="a5-error">{{ form.errors.phone }}</p></div>
                        <div v-if="checkoutFields.document"><label class="mb-1.5 block text-xs font-bold text-slate-400">CPF</label><input v-model="form.document" class="a5-input" inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" /><p v-if="form.errors.document" class="a5-error">{{ form.errors.document }}</p></div>
                    </div>
                    <p v-if="!checkoutFields.name && !checkoutFields.email && !checkoutFields.phone && !checkoutFields.document" class="rounded-2xl bg-white/[.035] p-4 text-sm text-slate-400">Esta campanha não solicita dados pessoais adicionais para a reserva.</p>

                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl bg-white/[.035] p-4 text-sm leading-5 text-slate-300"><input v-model="form.terms" type="checkbox" class="mt-1 accent-[var(--a5-primary)]" /><span>Li e aceito o regulamento desta campanha e confirmo os dados informados.</span></label>
                    <p v-if="form.errors.terms" class="a5-error">{{ form.errors.terms }}</p>

                    <div class="flex items-center justify-between rounded-2xl border a5-brand-border a5-brand-soft px-4 py-4"><div><div class="text-xs text-slate-400">{{ selectedQuantity }} números</div><div class="font-black">Pagamento via Pix</div></div><strong class="text-xl a5-brand-text">{{ money(total) }}</strong></div>
                    <button type="submit" :disabled="form.processing || !canContinue" class="w-full rounded-2xl a5-brand-bg px-5 py-4 font-black transition disabled:cursor-wait disabled:opacity-60">{{ form.processing ? 'Reservando com segurança...' : 'Reservar e gerar Pix' }}</button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
