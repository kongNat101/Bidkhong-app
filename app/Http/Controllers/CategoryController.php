<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // GET /api/categories - ดู categories ทั้งหมด
    public function index()
    {
        $categories = Category::with('subcategories')->get();
        return response()->json($categories);
    }

    // GET /api/categories/{id} - ดู category เดียวพร้อม subcategories
    public function show($id)
    {
        $category = Category::with('subcategories')->findOrFail($id);
        return response()->json($category);
    }

    // GET /api/subcategories - ดู subcategories ทั้งหมด
    public function subcategories()
    {
        $subcategories = Subcategory::with('category')->get();
        return response()->json($subcategories);
    }
}