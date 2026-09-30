<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MyNumbersLookupController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('MyNumbers/Lookup', [
            'lookupUrl' => route('my-numbers.lookup.submit'),
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ], [
            'phone.required' => 'Informe seu WhatsApp com DDD.',
        ]);

        try {
            $phone = Phone::normalize((string) $validated['phone']);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'phone' => 'WhatsApp inválido.',
            ]);
        }

        $rateKey = hash('sha256', (string) $request->ip().'|'.$phone);
        $phoneKey = hash('sha256', $phone);

        $minuteKey = 'my-numbers:minute:'.$rateKey;
        $hourKey = 'my-numbers:hour:'.$phoneKey;

        if (RateLimiter::tooManyAttempts($minuteKey, 5) || RateLimiter::tooManyAttempts($hourKey, 30)) {
            throw ValidationException::withMessages([
                'phone' => 'Muitas tentativas. Aguarde alguns minutos antes de tentar novamente.',
            ]);
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 3600);

        $customer = Customer::query()
            ->where('phone', $phone)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'phone' => 'WhatsApp não encontrado.',
            ]);
        }

        return redirect()->route('my-numbers.show', $customer->public_token);
    }
}
