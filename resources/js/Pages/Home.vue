<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '../Layouts/PublicLayout.vue';
import RaffleCard from '../Components/RaffleCard.vue';

defineProps<{ featured: any | null; raffles: any[]; finished: any[] }>();
</script>

<template>
    <Head title="Campanhas" />
    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 pb-6 pt-8 sm:px-6 sm:pt-12">
            <div class="mb-7 flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.22em] a5-brand-text">Campanhas em destaque</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Escolha sua campanha</h1>
                </div>
                <div class="hidden rounded-full border a5-brand-border a5-brand-soft px-4 py-2 text-xs font-bold a5-brand-text sm:block">Pagamento via Pix</div>
            </div>

            <RaffleCard v-if="featured" :raffle="featured" featured />

            <div v-else class="rounded-3xl border border-dashed border-white/12 bg-white/[.025] px-6 py-16 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl a5-brand-soft text-2xl">🎟️</div>
                <h2 class="mt-5 text-xl font-black">Nenhuma campanha ativa no momento</h2>
                <p class="mt-2 text-slate-400">Quando uma nova campanha for publicada, ela aparecerá aqui.</p>
            </div>

            <div v-if="raffles.length" class="mt-8 grid gap-5 md:grid-cols-2">
                <RaffleCard v-for="raffle in raffles" :key="raffle.uuid" :raffle="raffle" />
            </div>
        </section>

        <section v-if="finished.length" class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <div class="mb-5">
                <p class="text-xs font-black uppercase tracking-[.22em] text-slate-500">Histórico</p>
                <h2 class="mt-2 text-2xl font-black">Campanhas encerradas</h2>
            </div>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <RaffleCard v-for="raffle in finished" :key="raffle.uuid" :raffle="raffle" />
            </div>
        </section>
    </PublicLayout>
</template>
