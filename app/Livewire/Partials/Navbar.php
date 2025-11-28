<?php

namespace App\Livewire\Partials;

use App\Helpers\CartManagement;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

// This class handles the logic for the Navbar, such as checking if a user is logged in
class Navbar extends Component
{

    public $total_count = 0;
    // The mount method runs once when the component is first created.
    public function mount()
    {
       $this->total_count = count(CartManagement::getCartItemsFromCookie());
    }
    #[On('update-cart-count')]
    public function updateCartCount($total_count){
        $this->total_count = $total_count;

    }
    // The render method defines which Blade view to use for this component.
    public function render()
    {
        return view('livewire.partials.navbar', [
            // Pass the current authentication status to the view
            'user' => Auth::user(),
        ]);
    }
}