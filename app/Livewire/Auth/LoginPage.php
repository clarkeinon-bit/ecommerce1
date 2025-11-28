<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Session; // Import the Session Facade
use Illuminate\Support\Facades\Auth; // CRITICAL FIX: Import the Auth Facade

#[Title('Login')]
class LoginPage extends Component
{
    public $email;
    public $password;

    public function save()
    {
        $this->validate([
            'email' => 'required|email|max:255|exists:users,email',
            'password' => 'required|min:6|max:255',
        ]);

        // FIX: Use the imported Auth Facade to resolve the "Undefined method 'attempt'" error.
        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            // FIX: Use the imported Session Facade to prevent undefined function issues
            Session::flash('error', 'Invalid credentials');
            return;
        }

        // If login is successful, redirect to the home page (or dashboard)
        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.auth.login-page');
    }
}