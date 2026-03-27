<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWatch;
use Illuminate\Http\Request;

class ProductWatchController extends Controller
{
    // POST /api/products/{id}/watch — กดติดตาม/ยกเลิกติดตามสินค้า (toggle)
    public function toggle(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // ห้ามติดตามสินค้าตัวเอง
        if ($product->user_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot watch your own product'], 400);
        }

        $existing = ProductWatch::where('user_id', $request->user()->id)
            ->where('product_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'message' => 'Unwatched',
                'watching' => false,
            ]);
        }

        ProductWatch::create([
            'user_id' => $request->user()->id,
            'product_id' => $id,
        ]);

        return response()->json([
            'message' => 'Watching',
            'watching' => true,
        ], 201);
    }

    // GET /api/users/me/watchlist — สินค้าที่ติดตาม
    public function watchlist(Request $request)
    {
        $watches = ProductWatch::where('user_id', $request->user()->id)
            ->with(['product:id,name,picture,current_price,auction_start_time,auction_end_time,status'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($watches);
    }
}
