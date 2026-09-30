<script setup lang="ts">
const props = defineProps<{ raffle: any; featured?: boolean }>();

const money = (cents: number) => new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
}).format((cents || 0) / 100);

function trackCampaignClick() {
    const url = props.raffle?.analytics_url;
    if (!url) return;

    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';

    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ event: 'campaign_click' }),
    }).catch(() => undefined);
}
</script>

<template>
    <a :href="raffle.url" class="a5-card group block overflow-hidden rounded-3xl border border-white/8 bg-[#121d22] transition hover:-translate-y-1 hover:shadow-2xl" @click="trackCampaignClick">
        <div :class="featured ? 'aspect-[16/8]' : 'aspect-[16/9]'" class="relative overflow-hidden bg-gradient-to-br from-[#173029] via-[#14252a] to-[#0e171b]">
            <img v-if="raffle.cover_url" :src="raffle.cover_url" :alt="raffle.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" />
            <div v-else class="absolute inset-0 grid place-items-center a5-brand-soft">
                <span class="text-5xl font-black text-white/10">A5</span>
            </div>
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-5 pb-5 pt-16">
                <div class="inline-flex rounded-full a5-brand-bg px-3 py-1 text-xs font-black">{{ raffle.purchasable ? 'Adquira já' : raffle.status_label }}</div>
            </div>
        </div>
        <div class="p-5">
            <h2 :class="featured ? 'text-2xl sm:text-3xl' : 'text-lg'" class="font-black tracking-tight text-white">{{ raffle.title }}</h2>
            <div class="mt-3 flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[.16em] text-slate-500">Por número</p>
                    <strong class="text-lg a5-brand-text">{{ money(raffle.price_cents) }}</strong>
                </div>
                <span class="text-sm font-bold text-slate-300">{{ raffle.progress }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/7">
                <div class="h-full rounded-full a5-brand-progress transition-all" :style="{ width: `${raffle.progress}%` }"></div>
            </div>
            <div class="mt-3 flex justify-between text-xs text-slate-500">
                <span>{{ raffle.paid_numbers.toLocaleString('pt-BR') }} vendidos</span>
                <span>{{ raffle.available_numbers.toLocaleString('pt-BR') }} disponíveis</span>
            </div>
        </div>
    </a>
</template>
