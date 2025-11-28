<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url; // Needed for public $session_id to be populated from the URL
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Added for better error handling/logging

#[Title('Success - DCodeMania')]
class SuccessPage extends Component
{
    #[Url(as: 'session_id')] // This attribute maps the URL query parameter to the property
    public $session_id;

    public function render()
    {
        // 1. Fetch the latest order associated with the currently authenticated user
        // We use Auth::id() for cleaner access to the user's ID.
        $latest_order = Order::with('address')
            ->where('user_id', Auth::id())
            ->latest()
            ->first();

        // Check for session_id to process Stripe payment confirmation
        if ($this->session_id) {
            try {
                // Initialize Stripe with the secret key from the environment
                Stripe::setApiKey(env('STRIPE_SECRET'));

                // Retrieve the session information from Stripe
                $session_info = Session::retrieve($this->session_id);

                // Check the payment status
                if ($session_info->payment_status !== 'paid') {
                    // Payment failed or was cancelled
                    $latest_order->payment_status = 'failed';
                    $latest_order->save();
                    
                    // Redirect to a cancellation or failure page
                    // NOTE: Ensure the 'cancel' route is defined in routes/web.php
                    return redirect()->route('cancel');
                } elseif ($session_info->payment_status === 'paid') {
                    // Payment was successful
                    $latest_order->payment_status = 'paid';
                    $latest_order->save();
                }
            } catch (\Exception $e) {
                // Log the exception for debugging purposes
                Log::error("Stripe Session Retrieval Error: " . $e->getMessage());

                // Optional: set order status to failed if Stripe check fails unexpectedly
                if ($latest_order) {
                    $latest_order->payment_status = 'failed';
                    $latest_order->save();
                }

                // Redirect to a safe page or show an error
                return redirect('/checkout')->with('error', 'There was an issue processing the payment confirmation. Please check your order history.');
            }
        }

        // Return the view with the latest order data
        return view('livewire.success-page', [
            'order' => $latest_order,
        ]);
    }
}