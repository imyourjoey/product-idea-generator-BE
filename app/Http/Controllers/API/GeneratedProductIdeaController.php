<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\GeneratedProductIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneratedProductIdeaController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Retrieve all product ideas for the current user only
        $ideas = GeneratedProductIdea::where('user_id', $userId)->get();

        // Get the brand names and format timestamps for each idea
        $ideas->transform(function ($idea) {
            if (is_null($idea->brand_id)) {
                $idea->brand_name = '-';
            } else {
                // Find the corresponding brand name using the brand_id
                $brand = Brand::find($idea->brand_id);
                $idea->brand_name = $brand ? $brand->name : '-';
            }

            return $idea;
        });

        // Return the ideas as a JSON response
        return response()->json($ideas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'unique_selling_point' => 'required|string',
            'target_market' => 'required|string',
            'estimated_cost' => 'required|numeric',
            'estimated_selling_price' => 'required|numeric',
            'estimated_units_sold_per_month' => 'required|integer',
            'product_image' => 'nullable|string',
            'brand_id' => 'nullable|exists:brands,id',
            'user_id' => 'required|exists:users,id',
            'feasibility_score' => 'required|numeric',
            'category' => 'required|string',
        ]);

        $idea = GeneratedProductIdea::create($data);

        return response()->json($idea, 201);
    }
}
