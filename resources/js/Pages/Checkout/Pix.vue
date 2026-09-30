<script setup lang="ts">
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PublicLayout from '../../Layouts/PublicLayout.vue';

const props = defineProps<{ order: any; payment: any | null; urls: any }>();
const payment = ref<any>(props.payment);
const orderStatus = ref(props.order.status);
const loading = ref(!props.payment || !props.payment?.qr_code);
const error = ref('');
const copied = ref(false);
const copiedAccess = ref(false);
const qrImage = ref<string | null>(null);
const now = ref(Date.now());
let pollTimer: number | undefined;
let clockTimer: number | undefined;
let redirectTimer: number | undefined;
const redirectSeconds = ref(5);
const redirectScheduled = ref(false);

const money = (cents: number) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format((cents || 0) / 100);
const paid = computed(() => orderStatus.value === 'paid' || payment.value?.status === 'approved');
const automatic = computed(() => payment.value?.confirmation_mode === 'automatic');
const terminal = computed(() => ['paid', 'expired', 'cancelled', 'refunded', 'chargeback'].includes(orderStatus.value));
const reservationExpired = computed(() => Boolean(props.order.expires_at) && new Date(props.order.expires_at).getTime() <= now.value);
const remaining = computed(() => {
    if (!props.order.expires_at) return '';
    const ms = Math.max(0, new Date(props.order.expires_at).getTime() - now.value);
    const total = Math.floor(ms / 1000);
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

watch(() => payment.value?.qr_code, async (value) => {
    if (!value) {
        qrImage.value = null;
        return;
    }

    qrImage.value = await QRCode.toDataURL(String(value), {
        width: 360,
        margin: 1,
        errorCorrectionLevel: 'M',
    });
}, { immediate: true });

async function createPayment() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.post(props.urls.create_payment, { token: props.order.token });
        payment.value = data.payment;
    } catch (e: any) {
        error.value = e?.response?.data?.message || 'Não foi possível gerar o Pix agora. Tente novamente.';
    } finally {
        loading.value = false;
    }
}

async function poll() {
    if (terminal.value) return;
    try {
        const { data } = await axios.get(props.urls.payment_status, { headers: { 'X-Order-Token': props.order.token } });
        orderStatus.value = data.order.status;
        if (payment.value && data.payment) payment.value = { ...payment.value, ...data.payment };
        if (data.order.status === 'paid') {
            stopPolling();
            startSuccessRedirect();
        }
    } catch { /* mantém a tela e tenta novamente */ }
}

function stopPolling() {
    if (pollTimer) window.clearInterval(pollTimer);
    pollTimer = undefined;
}

function startSuccessRedirect() {
    if (!automatic.value || redirectScheduled.value || !props.order.raffle?.url) return;

    redirectScheduled.value = true;
    redirectSeconds.value = 5;

    redirectTimer = window.setInterval(() => {
        redirectSeconds.value -= 1;

        if (redirectSeconds.value <= 0) {
            if (redirectTimer) window.clearInterval(redirectTimer);
            redirectTimer = undefined;
            window.location.href = props.order.raffle.url;
        }
    }, 1000);
}

async function copyPix() {
    if (!payment.value?.qr_code) return;
    await navigator.clipboard.writeText(payment.value.qr_code);
    copied.value = true;
    window.setTimeout(() => copied.value = false, 1800);
}

async function copyAccessCode() {
    if (!props.order.customer?.access_pin) return;
    await navigator.clipboard.writeText(props.order.customer.access_pin);
    copiedAccess.value = true;
    window.setTimeout(() => copiedAccess.value = false, 1800);
}

onMounted(async () => {
    if (props.order.customer?.access_token) {
        window.localStorage.setItem('a5_customer_access_token', props.order.customer.access_token);
    }
    if (!payment.value?.qr_code && !paid.value) await createPayment();
    if (paid.value) startSuccessRedirect();
    if (!terminal.value) pollTimer = window.setInterval(poll, 5000);
    clockTimer = window.setInterval(() => now.value = Date.now(), 1000);
});

onBeforeUnmount(() => {
    stopPolling();
    if (clockTimer) window.clearInterval(clockTimer);
    if (redirectTimer) window.clearInterval(redirectTimer);
});
</script>

<template>
    <Head title="Pagamento Pix" />
    <PublicLayout>
        <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
            <div v-if="paid" class="mb-6 rounded-3xl border a5-brand-border a5-brand-soft p-6 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full a5-brand-bg text-3xl font-black">✓</div>
                <h1 class="mt-4 text-3xl font-black">PAGAMENTO CONFIRMADO COM SUCESSO</h1>
                <p class="mt-2 text-slate-300">Seus números estão confirmados.</p>
                <p v-if="automatic && redirectScheduled" class="mt-3 text-sm font-bold a5-brand-text">Você será redirecionado para a campanha em {{ redirectSeconds }} segundo{{ redirectSeconds === 1 ? '' : 's' }}.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[.9fr_1.1fr]">
                <aside class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-6">
                    <p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Resumo do pedido</p>
                    <h2 class="mt-2 text-xl font-black">{{ order.raffle.title }}</h2>
                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Cliente</span><b class="text-right">{{ order.customer.name }}</b></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Quantidade</span><b>{{ order.quantity }} números</b></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Total</span><b class="a5-brand-text">{{ money(order.total_cents) }}</b></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Status</span><b>{{ paid ? 'Pago' : orderStatus === 'expired' ? 'Expirado' : 'Aguardando confirmação' }}</b></div>
                        <div v-if="!paid && orderStatus !== 'expired'" class="flex justify-between gap-4"><span class="text-slate-500">Reserva expira em</span><b class="text-amber-300">{{ remaining }}</b></div>
                    </div>

                    <div class="mt-6 border-t border-white/8 pt-5">
                        <div class="flex items-center justify-between"><h3 class="font-black">Seus números</h3><span class="text-xs text-slate-500">{{ order.tickets.length }}</span></div>
                        <div class="mt-3 flex max-h-56 flex-wrap gap-2 overflow-y-auto pr-1">
                            <span v-for="ticket in order.tickets" :key="ticket" class="rounded-lg border a5-brand-border a5-brand-soft px-2.5 py-1.5 font-mono text-xs font-bold a5-brand-text">{{ ticket }}</span>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/8 bg-white/[.025] p-4">
                        <div class="font-black">Acesso aos seus números</div>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Guarde este código de 6 dígitos. Para consultar suas compras em outro aparelho, você precisará do WhatsApp e deste código.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <div class="w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-center">
                                <span class="text-[10px] font-black uppercase tracking-[.18em] text-slate-500">Código de acesso</span>
                                <div class="mt-1 font-mono text-2xl font-black tracking-[.32em] text-white">{{ order.customer.access_pin }}</div>
                            </div>
                            <a :href="order.customer.lookup_url" class="rounded-xl a5-brand-bg px-4 py-2 text-xs font-black">Meus números</a>
                            <button type="button" class="rounded-xl bg-white/8 px-4 py-2 text-xs font-black" @click="copyAccessCode">{{ copiedAccess ? 'Código copiado!' : 'Copiar código de acesso' }}</button>
                        </div>
                    </div>
                </aside>

                <div class="rounded-3xl border border-white/8 bg-[#121d22] p-5 sm:p-7">
                    <template v-if="paid">
                        <div class="py-8 text-center">
                            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full a5-brand-bg text-4xl font-black">✓</div>
                            <h2 class="mt-5 text-2xl font-black">PAGAMENTO CONFIRMADO COM SUCESSO</h2>
                            <p class="mt-2 text-sm text-slate-400">Seus números já estão confirmados nesta campanha.</p>
                            <p v-if="automatic && redirectScheduled" class="mt-4 text-sm font-bold a5-brand-text">Redirecionando em {{ redirectSeconds }} segundo{{ redirectSeconds === 1 ? '' : 's' }}...</p>
                            <a :href="order.raffle.url" class="mt-5 inline-flex rounded-2xl a5-brand-bg px-5 py-3 font-black">Voltar à campanha agora</a>
                        </div>
                    </template>
                    <template v-else-if="orderStatus === 'expired'">
                        <div class="py-8 text-center"><div class="text-5xl">⌛</div><h1 class="mt-4 text-2xl font-black">Reserva expirada</h1><p class="mt-2 text-slate-400">Os números foram liberados. Se você pagou depois do prazo, entre em contato com o atendimento antes de fazer uma nova compra.</p><a :href="order.raffle.url" class="mt-5 inline-flex rounded-2xl bg-white/10 px-5 py-3 font-bold">Voltar à campanha</a></div>
                    </template>
                    <template v-else-if="reservationExpired">
                        <div class="py-8 text-center">
                            <div class="text-5xl">⚠️</div>
                            <h1 class="mt-4 text-2xl font-black">Prazo de pagamento encerrado</h1>
                            <p v-if="automatic" class="mt-3 text-slate-400">O prazo encerrou e estamos fazendo uma conferência final automática no Banco Inter. Não gere outra compra enquanto esta tela estiver verificando.</p>
                            <p v-if="automatic" class="mt-3 text-sm text-amber-200">Se o Pix foi recebido dentro do prazo, seus números serão confirmados automaticamente. Se não houve pagamento, serão liberados após a conferência.</p>
                            <template v-else>
                                <p class="mt-3 text-slate-400">Não faça o pagamento agora. Como este Pix é estático, a reserva permanece bloqueada até conferência manual do atendimento.</p>
                                <p class="mt-3 text-sm text-amber-200">Se você já pagou antes do contador zerar, aguarde a confirmação. Se não pagou, os números serão liberados após a conferência bancária.</p>
                            </template>
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-center"><p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Pagamento via Pix</p><h1 class="mt-2 text-2xl font-black">Escaneie o QR Code</h1><p class="mt-2 text-sm text-slate-400">{{ automatic ? 'Após pagar, a confirmação acontece automaticamente.' : 'Após pagar, aguarde a confirmação do administrador.' }}</p></div>

                        <div v-if="loading" class="mt-8 animate-pulse rounded-3xl bg-white/5 py-28 text-center text-sm text-slate-500">Gerando Pix...</div>
                        <div v-else-if="error" class="mt-8 rounded-2xl border border-red-500/20 bg-red-500/8 p-5 text-center"><p class="font-bold text-red-300">{{ error }}</p><button class="mt-4 rounded-xl bg-white/10 px-4 py-2 text-sm font-bold" @click="createPayment">Tentar novamente</button></div>
                        <template v-else-if="payment">
                            <div class="mx-auto mt-7 max-w-xs rounded-3xl bg-white p-4 shadow-xl" v-if="qrImage"><img :src="qrImage" alt="QR Code Pix" class="aspect-square w-full object-contain" /></div>
                            <div class="mt-5 text-center"><span class="text-sm text-slate-400">Valor do Pix</span><div class="mt-1 text-3xl font-black a5-brand-text">{{ money(order.total_cents) }}</div></div>
                            <button v-if="payment.qr_code" type="button" class="mt-6 w-full rounded-2xl a5-brand-bg px-5 py-4 font-black" @click="copyPix">{{ copied ? 'Código copiado!' : 'Copiar Pix copia e cola' }}</button>
                            <div v-if="payment.qr_code" class="mt-4 max-h-28 overflow-hidden break-all rounded-2xl border border-white/8 bg-black/20 p-4 font-mono text-[11px] leading-5 text-slate-500">{{ payment.qr_code }}</div>
                            <div v-if="payment.txid || payment.external_reference" class="mt-4 text-center text-xs text-slate-500">Identificador: <b class="font-mono text-slate-300">{{ payment.txid || payment.external_reference }}</b></div>
                            <div v-if="automatic" class="mt-5 rounded-2xl border a5-brand-border a5-brand-soft p-4 text-sm leading-6 text-slate-200">
                                <b>Confirmação automática:</b> pague antes do contador zerar. O sistema recebe o aviso do Banco Inter e também faz consultas de contingência para confirmar o pagamento com segurança.
                            </div>
                            <div v-else class="mt-5 rounded-2xl border border-amber-500/20 bg-amber-500/8 p-4 text-sm leading-6 text-amber-100">
                                <b>Importante:</b> este é um Pix estático. Não efetue o pagamento depois que o contador chegar a zero. O código pode continuar tecnicamente válido, mas a reserva será liberada pelo sistema.
                            </div>
                            <div class="mt-5 flex items-center justify-center gap-2 text-xs text-slate-500"><span class="h-2 w-2 animate-pulse rounded-full bg-amber-400"></span> {{ automatic ? 'Aguardando confirmação automática do Banco Inter' : 'Aguardando confirmação manual do recebimento' }}</div>
                        </template>
                    </template>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
