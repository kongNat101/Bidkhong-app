<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    // จำนวน bid ขั้นต่ำที่ต้องมีก่อน recommend เฉพาะตัวจะเปิด
    const MIN_BIDS_FOR_READY = 3;

    // GET /api/recommendations/status — เช็คว่า user คนนี้พร้อมรับ recommend เฉพาะตัวหรือยัง
    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'ready' => false,
                'total_bids' => 0,
                'min_bids_required' => self::MIN_BIDS_FOR_READY,
                'message' => 'ต้อง login ก่อน',
            ]);
        }

        $totalBids = Bid::where('user_id', $user->id)->count();
        $isReady = $totalBids >= self::MIN_BIDS_FOR_READY;

        // หา category ที่ user bid มากที่สุด
        $topCategories = [];
        if ($isReady) {
            $topCategories = Bid::where('bids.user_id', $user->id)
                ->join('products', 'bids.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('categories.name', DB::raw('COUNT(*) as bid_count'))
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('bid_count')
                ->limit(3)
                ->get()
                ->map(fn($c) => ['category' => $c->name, 'bids' => $c->bid_count]);
        }

        return response()->json([
            'ready' => $isReady,
            'total_bids' => $totalBids,
            'min_bids_required' => self::MIN_BIDS_FOR_READY,
            'top_categories' => $topCategories,
        ]);
    }

    // GET /api/recommendations — แนะนำสินค้าเฉพาะตัวจากพฤติกรรมการ bid
    public function forUser(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 10);

        $totalBids = Bid::where('user_id', $user->id)->count();

        // ยังไม่พร้อม → ไม่แสดง recommend เฉพาะตัว
        if ($totalBids < self::MIN_BIDS_FOR_READY) {
            return response()->json([
                'source' => 'not_ready',
                'ready' => false,
                'total_bids' => $totalBids,
                'min_bids_required' => self::MIN_BIDS_FOR_READY,
                'products' => [],
            ]);
        }

        // === วิเคราะห์ category ที่ user ชอบ bid ===
        $categoryPreferences = Bid::where('bids.user_id', $user->id)
            ->join('products', 'bids.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('COUNT(*) as bid_count'))
            ->whereNotNull('products.category_id')
            ->groupBy('products.category_id')
            ->orderByDesc('bid_count')
            ->get();

        // รวม subcategory preferences ด้วย
        $subcategoryPreferences = Bid::where('bids.user_id', $user->id)
            ->join('products', 'bids.product_id', '=', 'products.id')
            ->select('products.subcategory_id', DB::raw('COUNT(*) as bid_count'))
            ->whereNotNull('products.subcategory_id')
            ->groupBy('products.subcategory_id')
            ->orderByDesc('bid_count')
            ->get();

        // สินค้าที่ user เคย bid แล้ว → ไม่แนะนำซ้ำ
        $bidProductIds = Bid::where('user_id', $user->id)
            ->pluck('product_id')
            ->toArray();

        // สินค้าของตัวเอง → ไม่แนะนำ
        $ownProductIds = Product::where('user_id', $user->id)
            ->pluck('id')
            ->toArray();

        $excludeIds = array_unique(array_merge($bidProductIds, $ownProductIds));

        // === ดึงสินค้าแนะนำ โดยให้น้ำหนักตาม category preference ===
        $categoryIds = $categoryPreferences->pluck('category_id')->toArray();
        $subcategoryIds = $subcategoryPreferences->pluck('subcategory_id')->toArray();

        if (empty($categoryIds)) {
            return $this->fallbackRecommendations($limit);
        }

        // สร้าง CASE statement สำหรับเรียงตาม category weight
        $categoryWeights = [];
        foreach ($categoryPreferences as $pref) {
            $categoryWeights[$pref->category_id] = $pref->bid_count;
        }

        $products = Product::with(['images', 'user:id,name,phone_number,profile_image'])
            ->withCount('bids')
            ->where('status', 'active')
            ->where('auction_end_time', '>', now())
            ->whereIn('category_id', $categoryIds)
            ->when(!empty($excludeIds), function ($q) use ($excludeIds) {
                $q->whereNotIn('id', $excludeIds);
            })
            ->get()
            ->map(function ($product) use ($categoryWeights, $subcategoryPreferences) {
                // คำนวณ relevance score
                $categoryScore = $categoryWeights[$product->category_id] ?? 0;

                // bonus ถ้าตรง subcategory ด้วย
                $subcategoryScore = $subcategoryPreferences
                    ->where('subcategory_id', $product->subcategory_id)
                    ->first();
                $subScore = $subcategoryScore ? $subcategoryScore->bid_count * 2 : 0;

                // bonus จากความนิยม (bid count)
                $popularityScore = min($product->bids_count, 10);

                $product->relevance_score = ($categoryScore * 10) + ($subScore * 5) + $popularityScore;
                return $product;
            })
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        // ถ้าได้ไม่พอ → เติมจาก popular
        if ($products->count() < $limit) {
            $remaining = $limit - $products->count();
            $existingIds = $products->pluck('id')->merge($excludeIds)->toArray();

            $extraProducts = Product::with(['images', 'user:id,name,phone_number,profile_image'])
                ->withCount('bids')
                ->where('status', 'active')
                ->where('auction_end_time', '>', now())
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('bids_count')
                ->limit($remaining)
                ->get();

            $products = $products->merge($extraProducts)->values();
        }

        // ซ่อน relevance_score จาก response
        $products->each(function ($p) {
            unset($p->relevance_score);
        });

        return response()->json([
            'source' => 'personalized',
            'ready' => true,
            'top_categories' => $categoryPreferences->take(3)->map(function ($pref) {
                $cat = \App\Models\Category::find($pref->category_id);
                return ['category' => $cat ? $cat->name : 'Unknown', 'bids' => $pref->bid_count];
            }),
            'products' => $products,
        ]);
    }

    // GET /api/products/{id}/similar — สินค้าที่คล้ายกัน (ดูจาก category + subcategory)
    public function similar($id, Request $request)
    {
        $limit = $request->input('limit', 10);
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // หาสินค้าใน subcategory เดียวกันก่อน แล้วค่อย category
        $products = collect();

        // 1. subcategory เดียวกัน (ตรงที่สุด)
        if ($product->subcategory_id) {
            $subProducts = Product::with(['images', 'user:id,name,phone_number,profile_image'])
                ->withCount('bids')
                ->where('status', 'active')
                ->where('auction_end_time', '>', now())
                ->where('id', '!=', $id)
                ->where('subcategory_id', $product->subcategory_id)
                ->orderByDesc('bids_count')
                ->limit($limit)
                ->get();
            $products = $products->merge($subProducts);
        }

        // 2. เติมจาก category เดียวกัน (ถ้ายังไม่พอ)
        if ($products->count() < $limit && $product->category_id) {
            $existingIds = $products->pluck('id')->push($id)->toArray();
            $remaining = $limit - $products->count();

            $catProducts = Product::with(['images', 'user:id,name,phone_number,profile_image'])
                ->withCount('bids')
                ->where('status', 'active')
                ->where('auction_end_time', '>', now())
                ->whereNotIn('id', $existingIds)
                ->where('category_id', $product->category_id)
                ->orderByDesc('bids_count')
                ->limit($remaining)
                ->get();
            $products = $products->merge($catProducts);
        }

        // 3. เติมจากสินค้าอื่นทั้งหมด (ถ้ายังไม่พอ)
        if ($products->count() < $limit) {
            $existingIds = $products->pluck('id')->push($id)->toArray();
            $remaining = $limit - $products->count();

            $otherProducts = Product::with(['images', 'user:id,name,phone_number,profile_image'])
                ->withCount('bids')
                ->where('status', 'active')
                ->where('auction_end_time', '>', now())
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('bids_count')
                ->limit($remaining)
                ->get();
            $products = $products->merge($otherProducts);
        }

        return response()->json([
            'source' => 'similar',
            'products' => $products->values(),
        ]);
    }

    // Fallback: สินค้า active ยอดนิยม
    private function fallbackRecommendations($limit)
    {
        $products = Product::with(['images', 'user:id,name,phone_number,profile_image'])
            ->withCount('bids')
            ->where('status', 'active')
            ->where('auction_end_time', '>', now())
            ->orderByDesc('bids_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'source' => 'fallback_popular',
            'ready' => false,
            'products' => $products,
        ]);
    }
}
