<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Mail\OrderPlaced;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe; 
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log; 

#[Title('Checkout')]
class CheckoutPage extends Component
{
    public $first_name;
    public $last_name;
    public $phone;
    public $street_address;
    public $city;
    public $state;
    public $zip_code;
    public $payment_method;

    public function mount()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();

        if (count($cart_items) === 0) {
            // FIX: Use Livewire's redirect method for internal navigation
            return $this->redirect('/products', navigate: true);
        }
    }

    public function placeOrder()
    {
        // 1. Validation
        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'phone' => 'required',
            'street_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip_code' => 'required',
            'payment_method' => 'required',
        ]);

        $cart_items = CartManagement::getCartItemsFromCookie();

        if (count($cart_items) === 0) {
              session()->flash('error', 'Cart is empty. Please add items to proceed.');
              return back();
        }

        // 2. Prepare Stripe Line Items
        $line_items = [];
        foreach ($cart_items as $item) {
            // Ensure unit_amount * 100 is an integer for Stripe
            $amount_in_paise = (int) round($item['unit_amount'] * 100); 

            $line_items[] = [
                'price_data' => [
                    'currency' => 'inr',
                    'unit_amount' => $amount_in_paise, 
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                ],
                'quantity' => $item['quantity'],
            ];
        }

        // 3. Create Order (Initial 'pending' order status)
        // Order details are saved before payment redirection
        $order = Order::create([
            'user_id' => Auth::id(),
            'grand_total' => CartManagement::calculateGrandTotal($cart_items),
            'payment_method' => $this->payment_method,
            'payment_status' => 'pending', 
            'status' => 'new',
            'currency' => 'inr',
            'shipping_amount' => 0,
            'shipping_method' => 'none',
            'notes' => 'Order placed by ' . Auth::user()->name,
        ]);

        // 4. Save Address
        Address::create([
            'order_id' => $order->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'street_address' => $this->street_address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
        ]);

        // 5. Save Order Items
        foreach ($cart_items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_amount' => $item['unit_amount'],
                'total_amount' => $item['total_amount'],
            ]);
        }
        
        // 6. Payment Handling
        if ($this->payment_method === 'stripe') {
            
            // Allow SSL bypass for local testing environment issues
            \Stripe\Stripe::setVerifySslCerts(false);
            // Set the API Key using the static method
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            try {
                // Use the static method on the Session class
                $session = \Stripe\Checkout\Session::create([ 
                    'payment_method_types' => ['card'],
                    'customer_email' => Auth::user()->email,
                    'line_items' => $line_items, 
                    'mode' => 'payment',
                    
                    // CRITICAL: Add metadata so SuccessPage can find the order
                    'metadata' => [
                        'order_id' => $order->id,
                    ],

                    // 🛑 FINAL FIX FOR THE {CHECKOUT_SESSION_ID} PLACEHOLDER 🛑
                    // Use concatenation to prevent Laravel from URL-encoding the placeholder.
                    'success_url' => route('success', ['order_id' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('cancel'),
                ]);
                
                // CRITICAL FIX: Use Livewire 3's EXTERNAL redirect method 
                // navigate: false forces a full browser redirect to Stripe.
                return $this->redirect($session->url, navigate: false); 

            } catch (\Exception $e) {
                Log::error('Stripe Checkout Error: ' . $e->getMessage());
                
                session()->flash('error', 'Payment processing failed. Please try again. Error: ' . $e->getMessage());

                // Delete the order since payment initiation failed
                $order->delete();
                
                return back();
            }
        }

        // 7. Cash on Delivery (COD) / Offline Payment Flow
        // This section is only reached if payment_method is NOT 'stripe'
        $order->update([
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        CartManagement::clearCartItems();
        Mail::to(Auth::user()->email)->send(new OrderPlaced($order));
        
        // ✅ FIX: Use Livewire 3's INTERNAL redirect method (best practice)
        return $this->redirect(route('success', ['order_id' => $order->id]), navigate: true);
    }

    public function render()
    {
        $cart_items = CartManagement::getCartItemsFromCookie();
        $grand_total = CartManagement::calculateGrandTotal($cart_items);
        
        return view('livewire.checkout-page', [
            'cart_items' => $cart_items,
            'grand_total' => $grand_total,
        ]);
    }
}