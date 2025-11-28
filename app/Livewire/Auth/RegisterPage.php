<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User; // CRITICAL: Import User Model
use Illuminate\Support\Facades\Hash; // CRITICAL: Import Hash facade
use Illuminate\Support\Facades\Auth; // ✅ FIX 1: Import the Auth facade

#[Title('Register')]
class RegisterPage extends Component {

    public $name;
    public $email;
    public $password;

    // register user
    public function save() {
        // 1. Validate the input fields
        $this->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        // 2. Save to database
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // 3. Log the user in and redirect (FIXED)
        Auth::login($user); // ✅ FIX 2: Use the imported Auth Facade for correct method calling
        
        // Redirect the user to the intended URL or the home page
        return redirect()->intended(); 
    }

    public function render() {
        return view('livewire.auth.register-page');
    }
}