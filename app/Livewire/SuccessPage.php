<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url; 
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session as LaravelSession; // Alias Session for clarity
use App\Helpers\CartManagement; // <-- 1. ADDED: Import CartManagement helper

#[Title('Success - DCodeMania')]
class SuccessPage extends Component
{
    // Livewire will correctly extract session_id from the query string
    #[Url(as: 'session_id')]
    public $session_id;

    // Livewire will correctly extract order_id from the query string
    #[Url(as: 'order_id')]
    public $order_id;

    public $latest_order; 

    /**
     * Executes once upon component load, ideal for redirects and fetching initial data.
     */
    public function mount()
    {
        $session_info = null;

        // 1. Process Stripe Payment Confirmation if session_id is present (i.e., Stripe just redirected here)
        if ($this->session_id) {
            
            $orderId = null;

            // 🛑 Check if the placeholder is still present 🛑
            if ($this->session_id === '{CHECKOUT_SESSION_ID}') {
                LaravelSession::flash('error', 'Critical Error: Stripe failed to replace the {CHECKOUT_SESSION_ID} placeholder. Check the success_url construction in CheckoutPage.php.');
                return $this->redirect('/checkout', navigate: true);
            }
            
            try {
                // Fetch the secret key from configuration
                $stripeSecret = config('services.stripe.secret');

                if (empty($stripeSecret)) {
                    throw new \Exception("Stripe secret key is missing from config('services.stripe.secret'). Check your .env file and run 'php artisan config:clear'.");
                }

                // Initialize Stripe
                \Stripe\Stripe::setApiKey($stripeSecret); 
                
                // 🛑 TEMPORARY FIX FOR SSL CERTIFICATE ERROR (errno 60) 🛑
                // This bypasses the local network SSL certificate issue. Remove this for production.
                \Stripe\Stripe::setVerifySslCerts(false);
    
                // Retrieve the session information from Stripe
                $session_info = Session::retrieve($this->session_id); 
                
            } catch (\Exception $e) {
                // Log the exception
                Log::error("Stripe Session Retrieval Failed (Session ID: {$this->session_id}): " . $e->getMessage());
                
                // Clear the session ID and redirect to prevent loops
                $this->session_id = null;

                $message = $e->getMessage();
                if (str_contains($message, 'SSL certificate problem')) {
                    $message = 'NETWORK ERROR: SSL verification failed. This is temporarily fixed in code, but check your PHP/network CA bundle for a permanent fix.';
                }
                    
                LaravelSession::flash('error', "Payment verification failed. Reason: " . $message);
                return $this->redirect('/checkout', navigate: true);
            }
            
            // 2. Find the SPECIFIC Order using Stripe Metadata and update its status
            if ($session_info && isset($session_info->metadata['order_id'])) {
                $orderId = $session_info->metadata['order_id'];
                $order = Order::find($orderId);
                
                if ($order) {
                    $this->latest_order = $order;
                    
                    if ($session_info->payment_status === 'paid' && $order->payment_status !== 'paid') {
                        // Success: Update status and save
                        $order->payment_status = 'paid';
                        $order->save();
                        
                        // 🛒 2. ADDED: Clear the cart after successful Stripe payment verification
                        CartManagement::clearCartItems();

                        Log::info("Order {$orderId} successfully paid and status updated. Cart cleared.");
                        
                    } elseif ($session_info->payment_status !== 'paid' && $order->payment_status !== 'failed') {
                        // Failed or pending but not paid
                        $order->payment_status = 'failed';
                        $order->save();
                        
                        // Redirect to cancellation page
                        return $this->redirect(route('cancel'), navigate: false); 
                    }
                }
            }

            // 3. Final Redirect to Clean URL (strips session_id)
            if ($orderId) {
                // Use a full redirect to replace the URL entirely and remove session_id
                return redirect()->route('success', ['order_id' => $orderId]);
            } else {
                 // If the order wasn't found via metadata (critical error), redirect home
                LaravelSession::flash('error', 'Payment processed, but order details could not be found via Stripe metadata.');
                return $this->redirect('/', navigate: true);
            }
        }
        
        // 4. If this is a clean URL (no session_id), find the order via order_id or latest Auth::id()
        if ($this->order_id) {
            // Find specific order (used after clean Stripe redirect or for COD)
            $this->latest_order = Order::with('address')
                ->where('id', $this->order_id)
                ->where('user_id', Auth::id())
                ->first();
        } else {
             // Fallback: get latest for Auth::id() if no session_id and no order_id (e.g., old bookmark)
             $this->latest_order = Order::with('address')
                ->where('user_id', Auth::id())
                ->latest()
                ->first();
        }

        // 🛑 FINAL NULL CHECK 🛑
        if (is_null($this->latest_order)) {
            LaravelSession::flash('error', 'We could not locate your recent order.');
            return $this->redirect('/', navigate: true);
        }
    }

    public function render()
    {
        // Pass the already fetched order to the view
        return view('livewire.success-page', [
            'order' => $this->latest_order,
        ]);
    }
}