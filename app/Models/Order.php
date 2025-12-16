<?php
// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'email',
        'quantity',
        'water_type',
        'water_price',
        'is_delivery',
        'delivery_fee',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_reference',
        'order_status',
        'delivery_user_id',
        'delivery_date',
        'delivery_notified_at',
        'late_alert_sent_at',
        'follow_up_sent_at',
        'notes',
        'replace_gallon',
        'replace_caps',
        'replacement_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_delivery' => 'boolean',
        'replace_gallon' => 'boolean',
        'replace_caps' => 'boolean',
        'delivery_date' => 'datetime',
        'water_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
        'delivery_notified_at' => 'datetime',
        'late_alert_sent_at' => 'datetime',
        'follow_up_sent_at' => 'datetime',
    ];

    /**
     * Calculate total amount based on quantity, price and delivery
     */
    public function calculateTotal()
    {
        $waterTotal = $this->quantity * $this->water_price;
        $deliveryTotal = $this->is_delivery ? ($this->quantity * $this->delivery_fee) : 0;
        return $waterTotal + $deliveryTotal;
    }

    /**
     * Get customer for this order
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get user who created this order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get delivery personnel assigned to this order
     */
    public function deliveryPerson()
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    /**
     * Get inventory transactions associated with this order
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get payments associated with this order
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate total amount paid for this order
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Calculate remaining balance for this order
     */
    public function getRemainingBalanceAttribute()
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    /**
     * Check if order is fully paid
     */
    public function isFullyPaid()
    {
        return $this->total_paid >= $this->total_amount;
    }
}