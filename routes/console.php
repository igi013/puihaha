<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('membership:admin {email} {--name=Administrator}', function () {
    $email = $this->argument('email');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Enter a valid email address.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('This email already exists. No account was changed.');

        return 1;
    }
    $password = $this->secret('Choose an administrator password (at least 12 characters)');
    if (! is_string($password) || strlen($password) < 12) {
        $this->error('Password must contain at least 12 characters.');

        return 1;
    }
    if ($password !== $this->secret('Confirm password')) {
        $this->error('Passwords do not match.');

        return 1;
    }
    $user = new User;
    $user->name = $this->option('name');
    $user->email = $email;
    $user->password = $password;
    $user->is_admin = true;
    $user->save();
    $this->info('Administrator created. Open /members to sign in.');

    return 0;
})->purpose('Create a membership administrator with a privately entered password');
