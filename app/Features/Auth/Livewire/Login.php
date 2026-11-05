<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Masuk')]
class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    public function submit(LoginUserAction $action)
    {
        $this->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $action->execute($this->login, $this->password, $this->remember);

        return $this->redirectRoute('home');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
