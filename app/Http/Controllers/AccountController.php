<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.edit');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => [
                'bail',
                'required',
                'string',
                'current_password:web',
            ],
            'password' => [
                'bail',
                'required',
                'string',
                'min:8',
                'different:current_password',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (strlen($value) > 72) {
                        $fail('A nova senha deve ter no máximo 72 bytes.');
                    }
                },
            ],
        ], [
            'current_password.required' =>
                'Informe sua senha atual.',
            'current_password.string' =>
                'Informe uma senha válida.',
            'current_password.current_password' =>
                'A senha atual está incorreta.',
            'password.required' =>
                'Informe a nova senha.',
            'password.string' =>
                'Informe uma nova senha válida.',
            'password.min' =>
                'A nova senha deve ter pelo menos 8 caracteres.',
            'password.different' =>
                'A nova senha deve ser diferente da senha atual.',
            'password.confirmed' =>
                'A confirmação da nova senha não confere.',
        ]);

        $user = $request->user();

        // O cast "hashed" do model User protege a senha ao salvar.
        $user->password = $data['password'];
        $user->remember_token = Str::random(60);
        $user->save();

        $request->session()->regenerate();

        return redirect()
            ->route('account.edit')
            ->with('status', 'Sua senha foi alterada com sucesso.');
    }
}