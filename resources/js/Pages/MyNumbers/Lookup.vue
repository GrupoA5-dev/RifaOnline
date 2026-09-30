<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';

const props = defineProps<{ lookupUrl: string }>();

const form = useForm({
    phone: '',
    });

function lookup() {
    form.post(props.lookupUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Meus números" />
    <PublicLayout>
        <section class="mx-auto max-w-xl px-4 py-12 sm:px-6 sm:py-16">
            <div class="rounded-3xl border border-white/8 bg-[#121d22] p-6 sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.18em] a5-brand-text">Área do participante</p>
                <h1 class="mt-2 text-3xl font-black">Meus números</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Informe o mesmo WhatsApp usado na compra da sua rifa.
                </p>

                <form class="mt-7 space-y-5" @submit.prevent="lookup">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-slate-400">WhatsApp com DDD</label>
                        <input
                            v-model="form.phone"
                            class="a5-input"
                            inputmode="tel"
                            autocomplete="tel"
                            maxlength="20"
                            placeholder="(83) 99999-9999"
                        />
                    </div>

                    
                    <p v-if="form.errors.phone" class="text-sm font-semibold text-red-400">{{ form.errors.phone }}</p>
                  
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-2xl a5-brand-bg px-5 py-4 font-black transition disabled:cursor-wait disabled:opacity-60"
                    >
                        {{ form.processing ? 'Consultando...' : 'Ver meus números' }}
                    </button>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>
