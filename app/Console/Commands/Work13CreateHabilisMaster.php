<?php

namespace App\Console\Commands;

use App\Models\Organizer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Throwable;

class Work13CreateHabilisMaster extends Command
{
    protected $signature = 'a5:work13-create-habilis-master';

    protected $description = 'Cadastra, com senha oculta, o Master de testes da Rifa Habilis, sem permissoes de plataforma.';

    public function handle(): int
    {
        $environment = config('app.env');
        $database = DB::connection()->getDatabaseName();

        if (
            ! (
                ($environment === 'staging' && $database === 'grupoaco_rifahomolog')
                || ($environment === 'testing' && app()->runningUnitTests())
            )
        ) {
            $this->error('PARADA: comando exclusivo da homologacao RifaOnline.');

            return self::FAILURE;
        }

        $organizer = Organizer::query()->where('slug', 'rifa-habilis')->first();
        $trial = Plan::query()->where('slug', 'trial')->where('active', true)->first();

        if (
            ! $organizer
            || $organizer->status !== 'active'
            || ! $trial
            || $organizer->users()->exists()
            || User::query()->where('role', 'super_admin')->count() !== 1
        ) {
            $this->error('PARADA: verifique o organizador, plano Trial, usuarios vinculados e Super Admin.');

            return self::FAILURE;
        }

        $this->info('Rifa Habilis: cadastro do Master (SEM acesso ao painel central Grupo A5).');

        $name = trim((string) $this->ask('Nome do Master'));
        $email = mb_strtolower(trim((string) $this->ask('E-mail do Master (diferente do Super Admin)')));
        $password = (string) $this->secret('Senha forte do Master (entrada oculta)');
        $passwordConfirmation = (string) $this->secret('Confirme a senha do Master (entrada oculta)');

        $validation = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validation->fails()) {
            $this->error('Cadastro nao realizado. Confira nome, e-mail unico e senha (12+ caracteres, maiuscula, minuscula, numero e simbolo).');

            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($organizer, $name, $email, $password): User {
                // A lock on the organizer row serializes competing first-Master registrations.
                $locked = Organizer::query()->whereKey($organizer->getKey())->lockForUpdate()->firstOrFail();

                if (
                    $locked->users()->exists()
                    || User::query()->where('email', $email)->exists()
                ) {
                    throw new RuntimeException('Cadastro Master duplicado.');
                }

                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => null,
                    'active' => true,
                ]);

                $locked->users()->attach($user->getKey(), ['role' => 'master']);

                return $user;
            });

            if (
                $user->fresh()->role !== null
                || ! DB::table('organizer_users')
                    ->where('organizer_id', $organizer->getKey())
                    ->where('user_id', $user->getKey())
                    ->where('role', 'master')
                    ->exists()
            ) {
                $this->error('Verificacao do vinculo do Master falhou. Interrompa a configuracao.');

                return self::FAILURE;
            }
        } catch (Throwable) {
            $this->error('Cadastro nao concluido. Nenhuma credencial foi exibida; consulte os logs locais.');

            return self::FAILURE;
        }

        $this->info('MASTER=CRIADO');
        $this->line('PAPEL_PLATAFORMA=NENHUM');
        $this->line('PAPEL_ORGANIZADOR=master');
        $this->line('ORGANIZADOR=rifa-habilis');
        $this->line('PORTAL_ORGANIZADOR=PENDENTE');
        $this->line('TRIAL=NAO_INICIADO');

        return self::SUCCESS;
    }
}
