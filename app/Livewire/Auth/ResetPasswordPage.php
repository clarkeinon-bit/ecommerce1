<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str; // CRITICAL FIX: Use the correct Str import
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Reset Password')]
class ResetPasswordPage extends Component
{
    public $token;

    #[Url]
    public $email;
    public $password;
    public $password_confirmation;

    public function mount($token) {
        $this->token = $token;
    }

    public function save(){
        $this->validate([
            // Use 'exists' rule for email to ensure it's a valid user
            'email' => 'required|email|exists:users,email', 
            // The Livewire property name for confirmation is implicitly used by 'confirmed'
            'password' => 'required|min:6|confirmed' 
        ]);

        $status = Password::broker()->reset( // Use broker() for explicit usage
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token
            ],
            function(User $user, string $newPassword){
                // FIX 1: The variable name was fixed to $newPassword (from $passowrd)
                // The password is automatically passed and hashed/saved by the broker 
                // in the closure if you don't manually save it.
                // However, if you do save it manually, ensure you use $newPassword.
                
                // OPTION A: Minimalist (Recommended, as the Password broker handles saving by default)
                // $user->forceFill(['password' => Hash::make($newPassword)])->save();
                
                // OPTION B: Full control (Matching your intent, fixing the logic)
                $user->forceFill([
                    'password' => Hash::make($newPassword)
                ])->save(); 
                
                // FIX 2: setRememberToken must be chained to the User instance, not forceFill
                $user->setRememberToken(Str::random(60));
                
                // The PasswordReset event is dispatched by the broker if you use the standard implementation, 
                // but explicitly calling it here is fine too if you prefer to be explicit.
                event(new PasswordReset($user));
            }
        );

        // FIX 3: Use named route redirect and check the status constant.
        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', 'Your password has been successfully reset! You can now log in.');
            return redirect()->route('login');
        } else {
            // Flash the translated error message returned by the broker (e.g., 'passwords.token')
            session()->flash('error', __($status)); 
        }
    }
    
    public function render()
    {
        return view('livewire.auth.reset-password-page');
    }
}