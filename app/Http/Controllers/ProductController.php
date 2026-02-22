<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    //GET /api/products = ดูรายละเอียดสินค้า (พร้อม search, filter, sort)
    public function index(Request $request)
    {
        $query = Product::with(['images', 'user:id,name,phone_number'])->withCount('bids');

        // 🔍 ค้นหาตามชื่อหรือรายละเอียด
        $query->when($request->search, function ($q, $search) {
            $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                }
                );
            });

        // 📂 กรองตาม category
        $query->when($request->category_id, function ($q, $categoryId) {
            $q->where('category_id', $categoryId);
        });

        // 📂 กรองตาม subcategory
        $query->when($request->subcategory_id, function ($q, $subcategoryId) {
            $q->where('subcategory_id', $subcategoryId);
        });

        // 💰 กรองตามราคาขั้นต่ำ
        $query->when($request->min_price, function ($q, $minPrice) {
            $q->where('current_price', '>=', $minPrice);
        });

        // 💰 กรองตามราคาสูงสุด
        $query->when($request->max_price, function ($q, $maxPrice) {
            $q->where('current_price', '<=', $maxPrice);
        });

        // 🏷 กรองตาม status (default: active)
        $query->when($request->status, function ($q, $status) {
            $q->where('status', $status);
        });

        // 📍 กรองตามสถานที่
        $query->when($request->location, function ($q, $location) {
            $q->where('location', 'like', "%{$location}%");
        });

        // 🏷 กรองตาม tag (hot, ending, incoming, ended)
        $query->when($request->tag, function ($q, $tag) {
            switch ($tag) {
                case 'hot':
                    $q->has('bids', '>=', 10);
                    break;
                case 'ending':
                    $q->where('status', 'active')
                        ->where('auction_end_time', '<=', now()->addHours(6))
                        ->where('auction_end_time', '>', now());
                    break;
                case 'incoming':
                    $q->where('auction_start_time', '>', now());
                    break;
                case 'ended':
                    $q->where('auction_end_time', '<', now());
                    break;
            }
        });

        // 🔄 Sort
        switch ($request->sort) {
            case 'price_asc':
                $query->orderBy('current_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('current_price', 'desc');
                break;
            case 'ending_soon':
                $query->where('status', 'active')
                    ->orderBy('auction_end_time', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(20);
        return response()->json($products);
    }

    //GET /api/products/{id} = ดูรายละเอียดสินค้าแค่ชิ้นเดียว
    public function show($id)
    {
        $product = Product::with(['images', 'user:id,name,phone_number'])->withCount('bids')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $response = $product->toArray();

        // ข้อมูลคนขาย
        $response['seller'] = [
            'name' => $product->user->name,
            'phone_number' => $product->user->phone_number,
        ];

        // ข้อมูล bids
        $response['total_bids'] = $product->bids_count;
        $response['latest_bidders'] = $product->bids()
            ->with('user:id,name')
            ->orderByDesc('time')
            ->take(5)
            ->get()
            ->map(fn($bid) => [
        'name' => $bid->user->name,
        'price' => $bid->price,
        'time' => $bid->time,
        ]);

        // bid increment + minimum bid
        $response['bid_increment'] = $product->bid_increment;
        $response['minimum_bid'] = $product->current_price + $product->bid_increment;

        // ข้อมูล certificate
        $response['is_certified'] = $product->is_certified;
        $response['certificate_status'] = $product->certificate?->status;

        // ข้อมูล seller rating
        $seller = $product->user;
        $response['seller']['average_rating'] = $seller->average_rating;
        $response['seller']['total_reviews'] = $seller->total_reviews;

        return response()->json($response);
    }

    //Post /api/products = สร้างสินค้าใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starting_price' => ['required', 'numeric', 'min:0'],
            'bid_increment' => ['required', 'numeric', 'min:1'],
            'buyout_price' => ['nullable', 'numeric', 'min:0'],
            'auction_start_time' => ['nullable', 'date', 'after_or_equal:now'],
            'auction_end_time' => ['nullable', 'date', 'after:now', 'required_without:duration'],
            'duration' => ['nullable', 'integer', 'in:1,2,3,4,5', 'required_without:auction_end_time'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'location' => ['nullable', 'string'],
            'picture' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpeg,png,jpg', 'max:10240'], // ใบเซอร์ max 10MB
        ]);

        // ถ้าส่ง duration → คำนวณ auction_end_time ให้
        if (isset($validated['duration']) && !isset($validated['auction_end_time'])) {
            $validated['auction_end_time'] = now()->addDays($validated['duration']);
        }
        unset($validated['duration']);

        // ถ้าไม่ส่ง auction_start_time → default = now()
        if (!isset($validated['auction_start_time'])) {
            $validated['auction_start_time'] = now();
        }

        // เพิ่ม user_id และ current_price
        $validated['user_id'] = $request->user()->id;
        $validated['current_price'] = $validated['starting_price'];

        // อัปโหลดรูปหลัก (บังคับ)
        $path = $request->file('picture')->store('products', 'public');
        $validated['picture'] = $path;

        // ลบ images ออกจาก validated ก่อน create (เพราะไม่ใช่ column ของ products)
        unset($validated['images']);

        $product = Product::create($validated);

        // อัปโหลดรูปเพิ่มเติม (Multiple Images)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $index,
                ]);
            }
        }

        // Load images relationship
        $product->load('images');

        // อัปโหลดใบเซอร์ (optional)
        if ($request->hasFile('certificate')) {
            $certFile = $request->file('certificate');
            $certPath = $certFile->store('certificates', 'public');

            ProductCertificate::create([
                'product_id' => $product->id,
                'file_path' => $certPath,
                'original_name' => $certFile->getClientOriginalName(),
                'status' => 'pending',
            ]);

            $product->load('certificate');
        }

        return response()->json($product, 201);
    }

    // DELETE /api/products/{id} — ลบสินค้า (เจ้าของเท่านั้น)
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // เช็คว่าเป็นเจ้าของสินค้า
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Only the product owner can delete this product'
            ], 403);
        }

        // ลบรูปหลัก
        if ($product->picture) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->picture);
        }

        // ลบรูปเพิ่มเติม
        foreach ($product->images as $image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_url);
            $image->delete();
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    // DELETE /api/products/{id}/images/{imageId} — ลบรูปสินค้า
    public function deleteImage(Request $request, $id, $imageId)
    {
        $product = Product::findOrFail($id);

        // เช็คว่าเป็นเจ้าของสินค้า
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Only the product owner can delete images'
            ], 403);
        }

        $image = $product->images()->findOrFail($imageId);

        // ลบไฟล์จาก storage (ใช้ path ตรง ๆ)
        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_url);

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }
}