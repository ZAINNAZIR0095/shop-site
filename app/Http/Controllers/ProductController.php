<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
 public function index()
    {
        $products = Product::latest()->paginate(10);
        return response()->json($products);
    }
 public function activeProducts()
    {
        $products = Product::where('status' , 'active')->get();
        return response()->json($products);
    }

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'type' => 'required|in:physical,digital,service',
        'unit' => 'nullable|string|max:50',
        'size' => 'nullable|string|max:100',
        'min_limit' => 'required|integer|min:0',
        'sale_price' => 'required|numeric|min:0',
        'purchase_price' => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $product = Product::create($request->all());

    // RETURN THIS EXACT STRUCTURE
    return response()->json([
        'success' => true,
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
}

    public function show(Product $id)
    {
        return response()->json($id);
    }

  public function update(Request $request, $id)
{
    // Find the product manually
    $product = Product::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'type' => 'required|in:physical,digital,service',
        'unit' => 'nullable|string|max:50',
        'size' => 'nullable|string|max:100',
        'min_limit' => 'required|integer|min:0',
        'sale_price' => 'required|numeric|min:0',
        'purchase_price' => 'required|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $product->update($request->all());

    return response()->json([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => $product
    ]);
}

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function updateStatus(Product $product, Request $request)
{
    $validated = $request->validate([
        'status' => 'required|in:active,inactive'
    ]);

    $product->update(['status' => $validated['status']]);

    return response()->json([
        'success' => true,
        'message' => 'Product status updated successfully',
        'validated' => $validated,
        'product' => $product,
    ]);
}
}
