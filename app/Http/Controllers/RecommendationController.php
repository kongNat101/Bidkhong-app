<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecommendationController extends Controller
{
    // GET /api/recommendations — แนะนำสินค้าให้ user ที่ login อยู่
    public function forUser(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 10);

        try {
            $response = Http::timeout(5)->get(
                config('services.recommendation.base_url') . "/recommend/{$userId}",
                ['limit' => $limit]
            );

            if ($response->successful()) {
                $productIds = $response->json('product_ids', []);
                $source = $response->json('source', 'unknown');

                if (!empty($productIds)) {
                    $products = Product::with(['images', 'user:id,name,phone_number'])
                        ->withCount('bids')
                        ->whereIn('id', $productIds)
                        ->where('status', 'active')
                        ->get()
                        ->sortBy(function ($product) use ($productIds) {
                            return array_search($product->id, $productIds);
                        })
                        ->values();

                    return response()->json([
                        'source' => $source,
                        'products' => $products,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Recommendation service ไม่ตอบ → ใช้ fallback
        }

        return $this->fallbackRecommendations($limit);
    }

    // GET /api/products/{id}/similar — สินค้าที่คล้ายกัน
    public function similar($id, Request $request)
    {
        $limit = $request->input('limit', 10);

        try {
            $response = Http::timeout(5)->get(
                config('services.recommendation.base_url') . "/similar/{$id}",
                ['limit' => $limit]
            );

            if ($response->successful()) {
                $productIds = $response->json('product_ids', []);
                $source = $response->json('source', 'unknown');

                if (!empty($productIds)) {
                    $products = Product::with(['images', 'user:id,name,phone_number'])
                        ->withCount('bids')
                        ->whereIn('id', $productIds)
                        ->where('status', 'active')
                        ->get()
                        ->sortBy(function ($product) use ($productIds) {
                            return array_search($product->id, $productIds);
                        })
                        ->values();

                    return response()->json([
                        'source' => $source,
                        'products' => $products,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Recommendation service ไม่ตอบ → ใช้ fallback
        }

        return $this->fallbackSimilar($id, $limit);
    }

    // Fallback: สินค้า active ยอดนิยม (เรียงตาม bid count)
    private function fallbackRecommendations($limit)
    {
        $products = Product::with(['images', 'user:id,name,phone_number'])
            ->withCount('bids')
            ->where('status', 'active')
            ->orderByDesc('bids_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'source' => 'fallback_popular',
            'products' => $products,
        ]);
    }

    // Fallback: สินค้าใน category เดียวกัน
    private function fallbackSimilar($id, $limit)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $query = Product::with(['images', 'user:id,name,phone_number'])
            ->withCount('bids')
            ->where('status', 'active')
            ->where('id', '!=', $id);

        if ($product->category_id) {
            $query->where('category_id', $product->category_id);
        }

        $products = $query->orderByDesc('bids_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'source' => 'fallback_category',
            'products' => $products,
        ]);
    }
}
