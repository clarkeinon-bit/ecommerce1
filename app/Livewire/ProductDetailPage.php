<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Product;


#[Title('Product Detail - DCodeMania')]
class ProductDetailPage extends Component
{
    public $slug;
    public $quantity = 1;

    public function mount($slug){
        $this->slug = $slug;

    }

    public function increaseQty(){
        $this->quantity++;
    }

    public function decreaseQty(){
        if($this->quantity > 1){
            $this->quantity--;

        }
    }

 public function addToCart($product_id)
    {
        try {
            // Add item to cart
            $total_count = CartManagement::addItemToCartWithQty($product_id, $this->quantity);

            // Update navbar cart count
            $this->dispatch('update-cart-count', total_count: $total_count)
                 ->to(Navbar::class);

          $this->dispatch('swal', [
    'icon' => 'success',
    'message' => 'Product added to cart successfully!',
]);



        } catch (\Exception $e) {

            // Trigger error alert
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Could not add product to cart!',
            ]);

        }
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            // Ensure you add the 'is_active' check to prevent users from seeing unpublished products
            'product' => Product::where('slug', $this->slug)->where('is_active', 1)->firstOrFail(),

        ]);
    }
}
