<?php

namespace App\Services;

use App\Exceptions\MailDeliveryException;
use App\Models\AdminAuditLog;
use App\Models\User;
use App\Notifications\SimpleAdminWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SimpleAdminService
{
    public function __construct(
        private MailConfigValidator $mailConfigValidator,
    ) {}

    public function generateTemporaryPassword(): string
    {
        return Str::password(length: 14, symbols: true);
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, phone?: string|null}  $data
     * @return array{admin: User, temporary_password: string, email_sent: bool}
     */
    public function create(array $data): array
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $admin = DB::transaction(function () use ($data, $temporaryPassword) {
            $admin = User::query()->create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($temporaryPassword),
                'role' => 'admin',
                'is_super_admin' => false,
                'is_active' => true,
                'first_login' => true,
            ]);

            AdminAuditLog::record('simple_admin.created', $admin, [
                'email' => $admin->email,
            ]);

            return $admin;
        });

        $emailSent = $this->deliverWelcomeEmail($admin, $temporaryPassword);

        return [
            'admin' => $admin,
            'temporary_password' => $temporaryPassword,
            'email_sent' => $emailSent,
        ];
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, phone?: string|null}  $data
     */
    public function update(User $admin, array $data): User
    {
        $this->ensureSimpleAdmin($admin);

        $admin->update([
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        AdminAuditLog::record('simple_admin.updated', $admin, [
            'email' => $admin->email,
        ]);

        return $admin->fresh();
    }

    public function suspend(User $admin): User
    {
        $this->ensureSimpleAdmin($admin);

        $admin->update(['is_active' => false]);

        DB::table('sessions')->where('user_id', $admin->id)->delete();

        AdminAuditLog::record('simple_admin.suspended', $admin);

        return $admin->fresh();
    }

    public function activate(User $admin): User
    {
        $this->ensureSimpleAdmin($admin);

        $admin->update(['is_active' => true]);

        AdminAuditLog::record('simple_admin.activated', $admin);

        return $admin->fresh();
    }

    public function delete(User $admin): void
    {
        $this->ensureSimpleAdmin($admin);

        AdminAuditLog::record('simple_admin.deleted', $admin, [
            'email' => $admin->email,
        ]);

        $admin->delete();
    }

    /**
     * @return array{temporary_password: string, email_sent: bool}
     */
    public function sendNewTemporaryPassword(User $admin): array
    {
        $this->ensureSimpleAdmin($admin);

        $temporaryPassword = $this->generateTemporaryPassword();

        $admin->update([
            'password' => Hash::make($temporaryPassword),
            'first_login' => true,
        ]);

        DB::table('sessions')->where('user_id', $admin->id)->delete();

        AdminAuditLog::record('simple_admin.temporary_password_sent', $admin);

        $emailSent = $this->deliverWelcomeEmail($admin, $temporaryPassword, isReset: true);

        return [
            'temporary_password' => $temporaryPassword,
            'email_sent' => $emailSent,
        ];
    }

    public function sendPasswordResetLink(User $admin): void
    {
        $this->ensureSimpleAdmin($admin);

        Password::sendResetLink(['email' => $admin->email]);

        AdminAuditLog::record('simple_admin.password_reset_link_sent', $admin);
    }

    public function completeFirstLogin(User $admin, string $newPassword): User
    {
        $this->ensureSimpleAdmin($admin);

        $admin->update([
            'password' => Hash::make($newPassword),
            'first_login' => false,
        ]);

        AdminAuditLog::record('simple_admin.password_changed', $admin);

        return $admin->fresh();
    }

    private function deliverWelcomeEmail(User $admin, string $temporaryPassword, bool $isReset = false): bool
    {
        try {
            $this->mailConfigValidator->ensureConfigured();
            $admin->notify(new SimpleAdminWelcomeNotification($temporaryPassword, $isReset));

            AdminAuditLog::record('simple_admin.welcome_email_sent', $admin, [
                'is_reset' => $isReset,
                'recipient' => $admin->email,
            ]);

            Log::info('Simple admin welcome email sent.', [
                'admin_id' => $admin->id,
                'recipient' => $admin->email,
                'is_reset' => $isReset,
            ]);

            return true;
        } catch (MailDeliveryException $exception) {
            report($exception);
            AdminAuditLog::record('simple_admin.welcome_email_failed', $admin, [
                'message' => $exception->getMessage(),
                'is_reset' => $isReset,
                'recipient' => $admin->email,
            ]);

            Log::error('Simple admin welcome email failed.', [
                'admin_id' => $admin->id,
                'recipient' => $admin->email,
                'message' => $exception->getMessage(),
                'is_reset' => $isReset,
            ]);

            return false;
        } catch (Throwable $exception) {
            report($exception);
            $message = MailDeliveryException::fromTransportFailure()->getMessage();

            AdminAuditLog::record('simple_admin.welcome_email_failed', $admin, [
                'message' => $message,
                'is_reset' => $isReset,
                'recipient' => $admin->email,
            ]);

            Log::error('Simple admin welcome email failed.', [
                'admin_id' => $admin->id,
                'recipient' => $admin->email,
                'message' => $exception->getMessage(),
                'is_reset' => $isReset,
            ]);

            return false;
        }
    }

    private function ensureSimpleAdmin(User $admin): void
    {
        if (! $admin->isSimpleAdmin()) {
            throw new InvalidArgumentException('This action is only allowed for simple admin accounts.');
        }
    }
}
