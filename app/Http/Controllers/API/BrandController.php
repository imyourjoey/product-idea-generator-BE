<?php

namespace App\Http\Controllers\API;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;
use App\Models\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;

class BrandController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get the currently authenticated user
        $user = Auth::user();
    
        // Retrieve brands belonging to the authenticated user
        $brands = $user->brands; // This will use the defined relationship
    
        // Map the brands to include only id and name
        $brandData = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
            ];
        });
    
        // Return a response with status, message, and the brand data
        return response()->json([
            'status' => 'success',
            'message' => 'Brands retrieved successfully.',
            'data' => $brandData,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //     ]);

    //     $brand = Brand::create([
    //         'name' => $request->name,
    //         'description' => $request->description,
    //         'user_id' => Auth::id(), 
    //     ]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Brand created successfully.',
    //         'brand' => $brand
    //     ], 201);
    // }
    public function store(Request $request)
    {
    // Validate incoming request
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'products' => 'array', // Validate that products is an array
        'products.*.name' => 'required|string|max:255', // Validate each product's name
        'products.*.description' => 'nullable|string', // Validate each product's description
    ]);

    // Create the brand
    $brand = Brand::create([
        'name' => $request->name,
        'description' => $request->description,
        'user_id' => Auth::id(),
    ]);

    // If products are provided, create them and associate with the brand
    if (!empty($request->products)) {
        foreach ($request->products as $productData) {
            // Create each product and associate it with the brand
            Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'] ?? null,
                'brand_id' => $brand->id, // Associate the product with the created brand
            ]);
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Brand created successfully.',
        'brand' => $brand,
    ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request data
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Find the brand by ID
        $brand = Brand::findOrFail($id); // Throws a 404 if not found

        // Update the brand with the validated data
        $brand->update($request->only(['name', 'description']));

        // Return the updated brand with status and message
        return response()->json([
            'status' => 'success',
            'message' => 'Brand updated successfully.',
            'brand' => $brand,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
