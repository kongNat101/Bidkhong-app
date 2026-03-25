<?php

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    // GET /api/search-history — ดึงประวัติค้นหา 20 อันล่าสุด
    public function index(Request $request)
    {
        $histories = SearchHistory::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return response()->json($histories);
    }

    // DELETE /api/search-history/{id} — ลบ 1 อัน
    public function destroy(Request $request, $id)
    {
        $history = SearchHistory::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$history) {
            return response()->json(['message' => 'Search history not found'], 404);
        }

        $history->delete();

        return response()->json(['message' => 'Search history deleted']);
    }

    // DELETE /api/search-history — ลบทั้งหมด (clear all)
    public function clearAll(Request $request)
    {
        $deleted = SearchHistory::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'All search history cleared',
            'deleted_count' => $deleted,
        ]);
    }
}
