<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_product_idea_id',
        'question',
        'answer',
    ];

    // Define the relationship with the GeneratedProductIdea model
    public function generatedProductIdea()
    {
        return $this->belongsTo(GeneratedProductIdea::class);
    }
}
