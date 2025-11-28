<?php

namespace App\Helpers;

use App\Models\Product;
use Illuminate\Support\Facades\Cookie;

class CartManagement{

    // --- Utility Methods for Cookie Management (CORRECTED) ---

    // add cart items to cookie
    static public function addCartItemsToCookie($cart_items){
        // Encode the PHP array into a JSON string before saving
        // Cookie duration set to 30 days (60 minutes * 24 hours * 30 days)
        Cookie::queue('cart_items', json_encode($cart_items), 60 * 24 * 30); 
    }

    // clear cart items from cookie
    static public function clearCartItems() {
        // Queue a command to forget the 'cart_items' cookie
        Cookie::queue(Cookie::forget('cart_items'));
    }

    /**
     * Retrieves all cart items from the cookie.
     *
     * @return array
     */
    static public function getCartItemsFromCookie(): array { // ADDED return type hint: array
        $cart_data_json = Cookie::get('cart_items');

        // CRITICAL FIX: Decode the JSON string back into a PHP array
        if ($cart_data_json) {
            return json_decode($cart_data_json, true);
        }

        // If the cookie is not set or empty, return an empty array
        return [];
    }


    // --- Cart Item Management Functions ---

    // add item to cart
    static public function addItemToCart($product_id) {
        $cart_items = self::getCartItemsFromCookie();
        $existing_item_key = null;

        // 1. Check if the item already exists in the cart
        foreach ($cart_items as $key => $item){
            if ($item['product_id'] == $product_id){
                $existing_item_key = $key;
                break;
            }
        }

        if ($existing_item_key !== null){
            // 2. If item exists, increment quantity and update total
            $cart_items[$existing_item_key]['quantity']++;
            $cart_items[$existing_item_key]['total_amount'] = $cart_items[$existing_item_key]['quantity'] *
            $cart_items[$existing_item_key]['unit_amount'];

        } else {
            // 3. If item does not exist, fetch product details and add a new entry
            $product = Product::where('id', $product_id)->first(['id','name','price','images']);
            if($product){
                
                // FIX: Use plural 'images' as defined in the Product model cast.
                // Check if 'images' is an array, not empty, and access the first index.
                // Otherwise, use a fallback image path ('placeholder.jpg').
                $first_image = (is_array($product->images) && !empty($product->images)) 
                               ? $product->images[0] 
                               : 'placeholder.jpg'; 
                
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product->name,
                    'image' => $first_image, // Use the safely retrieved image path
                    'quantity' => 1,
                    'unit_amount' => $product->price,
                    'total_amount' => $product->price
                ];
            }
        }

        self::addCartItemsToCookie($cart_items);
        return count($cart_items); // Returns the total number of unique items in the cart
    }
    //add item to cart with qty

      static public function addItemToCartWithQty($product_id, $qty = 1) {
        $cart_items = self::getCartItemsFromCookie();
        $existing_item_key = null;

        // 1. Check if the item already exists in the cart
        foreach ($cart_items as $key => $item){
            if ($item['product_id'] == $product_id){
                $existing_item_key = $key;
                break;
            }
        }

        if ($existing_item_key !== null){
            // 2. If item exists, increment quantity and update total
            $cart_items[$existing_item_key]['quantity'] = $qty;
            $cart_items[$existing_item_key]['total_amount'] = $cart_items[$existing_item_key]['quantity'] *
            $cart_items[$existing_item_key]['unit_amount'];

        } else {
            // 3. If item does not exist, fetch product details and add a new entry
            $product = Product::where('id', $product_id)->first(['id','name','price','images']);
            if($product){
                
                // FIX: Use plural 'images' as defined in the Product model cast.
                // Check if 'images' is an array, not empty, and access the first index.
                // Otherwise, use a fallback image path ('placeholder.jpg').
                $first_image = (is_array($product->images) && !empty($product->images)) 
                               ? $product->images[0] 
                               : 'placeholder.jpg'; 
                
                $cart_items[] = [
                    'product_id' => $product_id,
                    'name' => $product->name,
                    'image' => $first_image, // Use the safely retrieved image path
                    'quantity' => $qty,
                    'unit_amount' => $product->price,
                    'total_amount' => $product->price
                ];
            }
        }

        self::addCartItemsToCookie($cart_items);
        return count($cart_items); // Returns the total number of unique items in the cart
    }

    // remove item from cart
    static public function removeCartItem($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                unset($cart_items[$key]); // Remove the item by key
                    break;// Stop iteration once removed
            }
        }

        // Re-index the array keys after unsetting to prevent issues in the loop
        $cart_items = array_values($cart_items);
        
        self::addCartItemsToCookie($cart_items); 

        return $cart_items;
    }

    // increment item quantity
    static public function incrementQuantityToCartItem($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                // Increment the quantity
                $cart_items[$key]['quantity']++;
                
                // Recalculate the total amount for this specific item
                $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];
                break; 
            }
        }
        
        self::addCartItemsToCookie($cart_items); 

        return $cart_items;
    }

    // decrement item quantity
    static public function decrementQuantityToCartItem($product_id)
    {
        $cart_items = self::getCartItemsFromCookie();

        foreach ($cart_items as $key => $item) {
            if ($item['product_id'] == $product_id) {
                // CRITICAL CHECK: Only decrement if quantity is greater than 1
                if ($cart_items[$key]['quantity'] > 1) {
                    // Decrement the quantity
                    $cart_items[$key]['quantity']--;
                    
                    // Recalculate the total amount for this specific item
                    $cart_items[$key]['total_amount'] = $cart_items[$key]['quantity'] * $cart_items[$key]['unit_amount'];
                }
                break; 
            }
        }

        self::addCartItemsToCookie($cart_items); 

        return $cart_items;
    }

    // calculate grand total
    static public function calculateGrandTotal($items) {
        // Use array_sum to total the 'total_amount' column from all items
        return array_sum(array_column($items, 'total_amount'));
    }

}