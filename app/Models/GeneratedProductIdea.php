<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedProductIdea extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_name', 'description', 'unique_selling_point', 'target_market', 'estimated_cost', 'estimated_selling_price', 'estimated_units_sold_per_month', 'brand_id', 'user_id', 'feasibility_score', 'category',
    ];

    /**
     * Get the brand that owns the product idea.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
