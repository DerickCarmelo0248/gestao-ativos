<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-admin')]
#[Description('Cria uma conta administrativa pelo terminal')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Nome')),
            'email' => strtolower(trim((string) $this->ask('E-mail'))),
            'password' => $this->secret('Senha (mínimo de 8 caracteres)'),
            'password_confirmation' => $this->secret('Confirme a senha'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (strlen($value) > 72) {
                        $fail('A senha deve ter no máximo 72 bytes.');
                    }
                },
            ],
        ], [
            'name.required' => 'Informe o nome.',
            'name.max' => 'O nome deve ter até 255 caracteres.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter até 255 caracteres.',
            'email.unique' => 'Esse e-mail já está cadastrado.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'Use uma senha com pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->role = 'admin';
        $user->save();

        $this->info('Administrador criado com sucesso.');

        return self::SUCCESS;
    }
}