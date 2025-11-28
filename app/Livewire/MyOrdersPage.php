<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination; // Import the trait
use Illuminate\Support\Facades\Auth; // Use the Auth facade for user ID

#[Title('My Orders')]
class MyOrdersPage extends Component
{
    // Use the trait for pagination functionality
    use WithPagination;

    public function render()
    {
        // 1. Fetch the authenticated user's orders
        // 2. Filter orders where 'user_id' matches the current authenticated user's ID (Auth::id())
        // 3. Sort the results by the latest (descending 'created_at' by default)
        // 4. Paginate the results, showing 2 items per page (as implied by the original code's `paginate(2)`)
        $my_orders = Order::where('user_id', Auth::id())
            ->latest()
            ->paginate(5); // The image implies a small pagination count (e.g., 2)

        return view('livewire.my-orders-page', [
            'orders' => $my_orders,
        ]);
    }
}