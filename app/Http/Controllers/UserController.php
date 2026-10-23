<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function resetPasswordForm(Request $request, User $user): View
    {
        abort_unless($request->user()?->role === 'admin', 403);
        return view('users.reset-password', compact('user'));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);
        $data = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['bail', 'required', 'string', 'min:8', 'confirmed',
                function ($attribute, $value, $fail) {
                    if (strlen($value) > 72) {
                        $fail('A nova senha deve ter no máximo 72 bytes.');
                    }
                }],
        ], [
            'current_password.current_password' => 'Sua senha de administrador está incorreta.',
            'current_password.required' => 'Confirme sua senha de administrador.',
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'Use pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $locked->password = $data['password'];
            $locked->remember_token = \Illuminate\Support\Str::random(60);
            $locked->save();
            if (config('session.driver') === 'database') {
                \Illuminate\Support\Facades\DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))->where('user_id', $locked->id)->delete();
            }
        });
        return redirect()->route('users.index')->with('status', "Senha de {$user->name} redefinida com sucesso.");
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $normalized = [];

        if (is_string($request->input('name'))) {
            $normalized['name'] = trim($request->input('name'));
        }

        if (is_string($request->input('email'))) {
            $normalized['email'] = strtolower(
                trim($request->input('email'))
            );
        }

        $request->merge($normalized);

        $data = $request->validate([
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'bail',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => [
                'required',
                Rule::in(['admin', 'operator']),
            ],
            'password' => [
                'bail',
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
            'name.required' => 'Informe o nome do usuário.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter até 255 caracteres.',
            'email.required' => 'Informe o e-mail.',
            'email.string' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter até 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'role.required' => 'Selecione o perfil.',
            'role.in' => 'Selecione Administrador ou Operador.',
            'password.required' => 'Informe a senha.',
            'password.string' => 'A senha deve ser um texto.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        try {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role = $data['role'];
            $user->password = $data['password'];
            $user->save();
        } catch (UniqueConstraintViolationException $exception) {
            if (User::where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este e-mail já está cadastrado.',
                ]);
            }

            throw $exception;
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuário cadastrado com sucesso.');
    }
}