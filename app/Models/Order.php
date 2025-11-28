<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne; // <--- ADD THIS IMPORT

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'payment_status',
        'status',
        'currency',
        'shipping_method',
        'shipping_amount',
        'notes',
        'grand_total',
    ];

    // Cast monetary values to decimal to ensure precision
    protected $casts = [
        'grand_total' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Define the relationship to the Address model (Shipping/Billing).
     */
    public function address(): HasOne // <--- ADD THIS FUNCTION
    {
        // This links the Order to one Address record (the shipping/billing address)
        return $this->hasOne(Address::class);
    }
}