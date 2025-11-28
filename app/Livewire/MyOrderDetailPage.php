<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth; // Import Auth facade for security

#[Title('Order Detail')]
class MyOrderDetailPage extends Component
{
    // Public property to hold the order ID passed via the URL
    public $order_id;

    // Called automatically when the component is initialized, usually from a route parameter
    public function mount($order_id)
    {
        $this->order_id = $order_id;
    }

    public function render()
    {
        // 1. Fetch Order Items
        // Retrieves all items belonging to the current order ID, eager-loading the 'product' relationship.
        $order_items = OrderItem::with('product')
            ->where('order_id', $this->order_id)
            ->get();

        // 2. Fetch Order Address
        // Retrieves the shipping/billing address associated with the current order ID.
        // It uses first() since an order typically has one address associated.
        $address = Address::where('order_id', $this->order_id)
            ->first();
        
        // 3. Fetch the Order details
        // Retrieves the main order record based on the ID.
        // IMPORTANT: Add security check to ensure the order belongs to the authenticated user.
        $order = Order::where('id', $this->order_id)
            ->where('user_id', Auth::id()) // Security check: Only allow access to the user's own orders
            ->first();
        
        // Handle case where order is not found or does not belong to the user
        if (!$order) {
            // Option 1: Abort (returns 404 or an error)
            // abort(404); 
            
            // Option 2: Redirect to the orders list with an error message
            return redirect()->route('my-orders')->with('error', 'Order not found or access denied.');
        }

        return view('livewire.my-order-detail-page', [
            'order_items' => $order_items,
            'address' => $address,
            'order' => $order,
        ]);
    }
}