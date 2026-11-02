<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {--name=} {--email=} {--password=}';

    protected $description = 'Buat (atau reset password) akun admin platform';

    public function handle(): int
    {
        $name     = $this->option('name')     ?: $this->ask('Nama');
        $email    = $this->option('email')    ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        $v = Validator::make(compact('name', 'email', 'password'), [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'password' => 'required|string|min:8',
        ]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }
            return self::FAILURE;
        }

        $admin = Admin::where('email', $email)->first();

        if ($admin) {
            if (! $this->confirm("Admin {$email} sudah ada. Reset nama & password?")) {
                return self::SUCCESS;
            }
            $admin->update(['name' => $name, 'password' => $password, 'is_active' => true]);
            $this->info("Admin {$email} diperbarui.");
        } else {
            Admin::create(['name' => $name, 'email' => $email, 'password' => $password]);
            $this->info("Admin {$email} dibuat. Login di /admin/login");
        }

        return self::SUCCESS;
    }
}
