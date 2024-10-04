<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class ProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {
        // Get brand_id from the request body
        $brandId = $request->input('brand_id');

        if ($brandId) {
            // Retrieve the brand with its products
            $brand = Brand::with('products')->find($brandId);

            // Return error if brand not found
            if (! $brand) {
                return $this->sendError('Brand not found.');
            }

            // Response with brand details and products
            return $this->sendResponse([
                'brand' => [
                    'brand_id' => $brand->id,
                    'name' => $brand->name,
                    'description' => $brand->description,
                ],
                'products' => ProductResource::collection($brand->products),
            ], 'Products retrieved successfully.');

        } else {
            // Optionally, return all products without filtering by brand
            $products = Product::with('brand')->get();

            return $this->sendResponse([
                'products' => ProductResource::collection($products),
            ], 'All products retrieved successfully.');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'description' => 'required', // Updated field name
            'brand_id' => 'nullable|exists:brands,id', // Added brand_id validation
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $product = Product::create($input);

        return $this->sendResponse(new ProductResource($product), 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id): JsonResponse
    {
        $product = Product::find($id);

        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        return $this->sendResponse(new ProductResource($product), 'Product retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'description' => 'required', // Updated field name
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $product->name = $input['name'];
        $product->description = $input['description'];
        $product->save();

        return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->sendResponse([], 'Product deleted successfully.');
    }
}
