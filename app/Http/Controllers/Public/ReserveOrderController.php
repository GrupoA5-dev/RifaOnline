<?php

namespace App\Http\Controllers\Public;

use App\Actions\Raffles\ReserveRandomTickets;
use App\Actions\Raffles\ReserveSelectedTickets;
use App\Enums\AllocationMode;
use App\Exceptions\InsufficientTicketsException;
use App\Exceptions\InvalidPurchaseQuantityException;
use App\Exceptions\RaffleUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicOrderRequest;
use App\Models\Customer;
use App\Models\Raffle;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReserveOrderController extends Controller
{
    public function __invoke(
        StorePublicOrderRequest $request,
        string $slug,
        ReserveRandomTickets $reserveRandomTickets,
        ReserveSelectedTickets $reserveSelectedTickets,
    ): RedirectResponse {
        $raffle = Raffle::query()->where('slug', $slug)->firstOrFail();
        $validated = $request->validated();

        $name = $raffle->collect_name ? trim((string) ($validated['name'] ?? '')) : '';
        $email = $raffle->collect_email ? mb_strtolower(trim((string) ($validated['email'] ?? ''))) : null;
        $document = $raffle->collect_document
            ? preg_replace('/\D+/', '', (string) ($validated['document'] ?? ''))
            : null;
        try {
            $phone = Phone::normalize((string) ($validated['phone'] ?? ''));
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'phone' => 'Informe um telefone/WhatsApp válido com DDD.',
            ]);
        }

        // O WhatsApp é a chave única do cadastro público do cliente.
        $customer = $this->resolveCustomer($phone);

        if (! $customer) {
            $customer = new Customer();
        }

        if ($raffle->collect_name) {
            $customer->name = $name;
        } elseif (! $customer->exists || blank($customer->name)) {
            $customer->name = 'Participante '.Str::upper(Str::random(6));
        }

        if ($raffle->collect_email) {
            $customer->email = $email;
        }

        $customer->phone = $phone;

        if ($raffle->collect_document) {
            $customer->document_type = 'CPF';
            $customer->document_number = $document;
        }

        $customer->save();

        try {
            if ($raffle->allocation_mode === AllocationMode::Manual) {
                $order = $reserveSelectedTickets->handle(
                    $raffle,
                    $customer,
                    array_map('intval', $validated['numbers'] ?? []),
                );
            } else {
                $order = $reserveRandomTickets->handle(
                    $raffle,
                    $customer,
                    (int) $validated['quantity'],
                );
            }
        } catch (InvalidPurchaseQuantityException|InsufficientTicketsException|RaffleUnavailableException $exception) {
            $field = $raffle->allocation_mode === AllocationMode::Manual ? 'numbers' : 'quantity';

            throw ValidationException::withMessages([
                $field => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $raffle->allocation_mode === AllocationMode::Manual ? 'numbers' : 'quantity'
                    => 'Não foi possível reservar os números agora. Atualize a seleção e tente novamente.',
            ]);
        }

        return redirect()->route('checkout.show', [
            'orderUuid' => $order->uuid,
            'token' => $order->public_token,
        ]);
    }

    private function resolveCustomer(string $phone): ?Customer
    {
        return Customer::query()
            ->where('phone', $phone)
            ->first();
    }
}
