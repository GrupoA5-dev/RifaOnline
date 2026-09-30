<?php

namespace App\Services\Notifications;

use App\Models\Order;
use App\Models\SystemSetting;
use App\Support\Money;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

final class EmailNotificationService
{
    public function notifyNewOrder(Order $order): void
    {
        if (! $this->enabled('notify_new_order')) {
            return;
        }

        $order->loadMissing(['raffle:id,title', 'customer:id,name,email,phone']);
        $subject = 'Novo pedido #'.$order->getKey().' — '.($order->raffle?->title ?? 'Campanha');
        $body = implode("\n", [
            'Um novo pedido foi criado.',
            '',
            'Pedido: #'.$order->getKey(),
            'Campanha: '.($order->raffle?->title ?? '—'),
            'Cliente: '.($order->customer?->name ?? 'Participante'),
            'Quantidade: '.number_format((int) $order->quantity, 0, ',', '.'),
            'Valor: R$ '.Money::format((int) $order->total_cents),
            'Status: aguardando pagamento',
            'Criado em: '.($order->created_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')),
        ]);

        $this->safeSend($subject, $body);
    }

    public function notifyPaymentConfirmed(Order $order): void
    {
        if (! $this->enabled('notify_payment_confirmed')) {
            return;
        }

        $order->loadMissing(['raffle:id,title', 'customer:id,name,email,phone']);
        $subject = 'Pagamento confirmado #'.$order->getKey().' — '.($order->raffle?->title ?? 'Campanha');
        $body = implode("\n", [
            'Pagamento confirmado com sucesso.',
            '',
            'Pedido: #'.$order->getKey(),
            'Campanha: '.($order->raffle?->title ?? '—'),
            'Cliente: '.($order->customer?->name ?? 'Participante'),
            'Quantidade: '.number_format((int) $order->quantity, 0, ',', '.'),
            'Valor: R$ '.Money::format((int) $order->total_cents),
            'Pago em: '.($order->paid_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i')),
        ]);

        $this->safeSend($subject, $body);
    }

    public function sendTest(?string $recipient = null): void
    {
        $to = $recipient ?: $this->recipient();

        if ($to === '') {
            throw new RuntimeException('Informe o e-mail que deve receber as notificações.');
        }

        $this->send($to, 'Teste de notificações — A5 Rifas', "Configuração de e-mail validada.\n\nEnviado em ".now()->format('d/m/Y H:i').'.');
    }

    private function enabled(string $eventKey): bool
    {
        return filter_var(SystemSetting::value('notifications', 'email_enabled', '0'), FILTER_VALIDATE_BOOL)
            && filter_var(SystemSetting::value('notifications', $eventKey, '0'), FILTER_VALIDATE_BOOL);
    }

    private function safeSend(string $subject, string $body): void
    {
        try {
            $to = $this->recipient();

            if ($to === '') {
                return;
            }

            $this->send($to, $subject, $body);
        } catch (Throwable $e) {
            Log::warning('Falha ao enviar notificação por e-mail.', [
                'message' => $e->getMessage(),
                'subject' => $subject,
            ]);
        }
    }

    private function send(string $to, string $subject, string $body): void
    {
        $host = trim((string) SystemSetting::value('notifications', 'smtp_host', ''));
        $port = (int) SystemSetting::value('notifications', 'smtp_port', 587);
        $username = trim((string) SystemSetting::value('notifications', 'smtp_username', ''));
        $password = $this->smtpPassword();
        $encryption = trim((string) SystemSetting::value('notifications', 'smtp_encryption', 'tls'));
        $fromAddress = trim((string) SystemSetting::value('notifications', 'from_address', ''));
        $fromName = trim((string) SystemSetting::value('notifications', 'from_name', SystemSetting::value('general', 'system_name', config('app.name'))));

        if ($host === '' || $port <= 0 || $fromAddress === '') {
            throw new RuntimeException('A configuração SMTP está incompleta. Informe servidor, porta e e-mail remetente.');
        }

        config([
            'mail.mailers.a5_notifications' => [
                'transport' => 'smtp',
                'host' => $host,
                'port' => $port,
                'encryption' => in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null,
                'username' => $username !== '' ? $username : null,
                'password' => $password !== '' ? $password : null,
                'timeout' => 12,
                'local_domain' => null,
            ],
        ]);

        Mail::mailer('a5_notifications')->raw($body, function ($message) use ($to, $subject, $fromAddress, $fromName): void {
            $message->to($to)
                ->from($fromAddress, $fromName !== '' ? $fromName : null)
                ->subject($subject);
        });
    }

    private function recipient(): string
    {
        return trim((string) SystemSetting::value('notifications', 'recipient_email', ''));
    }

    private function smtpPassword(): string
    {
        $encrypted = SystemSetting::value('notifications', 'smtp_password');

        if (! is_string($encrypted) || $encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable) {
            return '';
        }
    }
}
