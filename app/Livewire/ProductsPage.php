<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Illuminate\Support\Facades\Log;

#[Title('Products - DCodeMania')]
class ProductsPage extends Component
{
    use WithPagination;

    #[Url]
    public $selected_categories = [];

    #[Url]
    public $selected_brands = [];

    #[Url]
    public $featured;

    #[Url]
    public $on_sale;

    #[Url]
    public $price_range = 300000;

    #[Url]
    public $sort = 'latest';

    /**
     * Add product to cart + trigger SweetAlert2 toast
     */
    public function addToCart($product_id)
    {
        try {
            // Add item to cart
            $total_count = CartManagement::addItemToCart($product_id);

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
        $productQuery = Product::query()
            ->where('is_active', 1)
            ->where('in_stock', 1);

        // Category filter
        if (!empty($this->selected_categories)) {
            $productQuery->whereIn('category_id', $this->selected_categories);
        }

        // Brand filter
        if (!empty($this->selected_brands)) {
            $productQuery->whereIn('brand_id', $this->selected_brands);
        }

        // Featured
        if ($this->featured) {
            $productQuery->where('is_featured', 1);
        }

        // On sale
        if ($this->on_sale) {
            $productQuery->where('on_sale', 1);
        }

        // Price range
        if ($this->price_range) {
            $productQuery->whereBetween('price', [0, $this->price_range]);
        }

        // Sorting
        if ($this->sort === 'latest') {
            $productQuery->latest();
        }

        if ($this->sort === 'price') {
            $productQuery->orderBy('price');
        }

        return view('livewire.products-page', [
            'products' => $productQuery->paginate(9),
            'brands' => Brand::where('is_active', 1)->get(['id', 'name', 'slug']),
            'categories' => Category::where('is_active', 1)->get(['id', 'name', 'slug']),
        ]);
    }
}
