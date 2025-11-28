<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'images',
        'price',
        'quantity', // ✅ Re-added: This is the actual stock/inventory count
        'is_active', 
        'is_featured', 
        'in_stock', 
        'on_sale',
        'category_id', 
        'brand_id',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',   // ✅ Added casting for price consistency
        'quantity' => 'integer',  // ✅ Added casting for stock quantity
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'in_stock' => 'boolean',
        'on_sale' => 'boolean',
    ];

    /**
     * Define the relationship to the Category model.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    /**
     * Define the relationship to the Brand model.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
    
    /**
     * Define the relationship to the OrderItem model (one product can be in many order items).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Reduces the stock quantity of the product by the given amount.
     * This method is typically called after an order is successfully placed or shipped.
     *
     * @param int $amount
     * @return bool Returns true if stock was successfully reduced, false otherwise.
     */
    public function reduceStock(int $amount): bool
    {
        if ($this->quantity < $amount) {
            // Cannot reduce stock if the requested amount is higher than available stock
            return false;
        }

        // Decrement the quantity and save the model
        $this->decrement('quantity', $amount);

        // Update the in_stock flag based on new quantity
        $this->in_stock = $this->quantity > 0;
        $this->save();

        return true;
    }
}