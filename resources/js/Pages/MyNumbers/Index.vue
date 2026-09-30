<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import PublicLayout from '../../Layouts/PublicLayout.vue';

const props = defineProps<{ customer: any; orders: any[] }>();

onMounted(() => {
    if (props.customer?.token) {
        window.localStorage.setItem('a5_customer_access_token', props.customer.token);
    }
});

function statusClass(status: string) {
    return status === 'paid'
        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
        : status === 'awaiting_payment'
            ? 'border-amber-500/20 bg-amber-500/10 text-amber-200'
            : 'border-white/10 bg-white/5 text-slate-300';
}
</script>

<template>
    <Head title="Meus números" />
    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
            <div class="mb-7">
                <p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Área do participante</p>
                <h1 class="mt-2 text-3xl font-black">Meus números</h1>
                <p class="mt-2 text-sm text-slate-400">Olá, {{ customer.name }}. Aqui ficam seus pedidos e números.</p>
            </div>

            <div v-if="!orders.length" class="rounded-3xl border border-white/8 bg-[#121d22] p-8 text-center text-slate-400">
                Nenhum pedido encontrado para este acesso.
            </div>

            <div v-else class="space-y-5">
                <article v-for="order in orders" :key="order.uuid" class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <a v-if="order.raffle.url" :href="order.raffle.url" class="text-xl font-black hover:underline">{{ order.raffle.title }}</a>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span>Pedido #{{ order.id }}</span>
                                <span>•</span>
                                <span>{{ order.created_at }}</span>
                                <span>•</span>
                                <span>{{ order.total }}</span>
                            </div>
                        </div>
                        <span class="rounded-full border px-3 py-1 text-xs font-black" :class="statusClass(order.status)">{{ order.status_label }}</span>
                    </div>

                    <div class="mt-5 border-t border-white/8 pt-5">
                        <div class="flex items-center justify-between gap-4"><h2 class="font-black">Números</h2><span class="text-xs text-slate-500">{{ order.tickets.length }}</span></div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-for="ticket in order.tickets" :key="ticket" class="rounded-lg border a5-brand-border a5-brand-soft px-2.5 py-1.5 font-mono text-xs font-bold a5-brand-text">{{ ticket }}</span>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a :href="order.checkout_url" class="rounded-xl bg-white/8 px-4 py-2 text-xs font-black hover:bg-white/12">Abrir pedido</a>
                        <a v-if="order.raffle.url" :href="order.raffle.url" class="rounded-xl a5-brand-bg px-4 py-2 text-xs font-black">Ver campanha</a>
                    </div>
                </article>
            </div>
        </section>
    </PublicLayout>
</template>
