<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {email? : E-mail de l’admin}
                            {--name= : Nom affiché (défaut : Admin Ôhéfê)}
                            {--force : Remplace le mot de passe si le compte existe déjà}';

    protected $description = 'Crée un compte admin Filament avec un mot de passe généré automatiquement';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('E-mail admin');
        $name = $this->option('name') ?: $this->ask('Nom affiché', 'Admin Ôhéfê');

        try {
            validator(
                ['email' => $email, 'name' => $name],
                [
                    'email' => ['required', 'email', 'max:255'],
                    'name' => ['required', 'string', 'max:120'],
                ],
            )->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $email));
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null && ! $existing->isAdmin() && ! $this->option('force')) {
            $this->error("L’e-mail {$email} est déjà utilisé par un compte {$existing->role?->label()}.");
            $this->line('Utilisez --force uniquement pour promouvoir ce compte en admin (nouveau mot de passe).');

            return self::FAILURE;
        }

        if ($existing?->isAdmin() && ! $this->option('force')) {
            $this->warn("Un admin existe déjà pour {$email}.");
            $this->line('Relancez avec --force pour régénérer le mot de passe.');

            return self::FAILURE;
        }

        $password = $this->generatePassword();

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
                'is_admin' => true,
                'can_publish' => true,
            ],
        );

        $this->newLine();
        $this->info($existing ? 'Admin mis à jour.' : 'Admin créé.');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Nom', $admin->name],
                ['E-mail', $admin->email],
                ['Mot de passe', $password],
                ['Panel', url('/admin')],
            ],
        );
        $this->warn('Notez le mot de passe maintenant — il ne sera plus réaffiché.');

        return self::SUCCESS;
    }

    private function generatePassword(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%&*?';
        $max = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }
}
