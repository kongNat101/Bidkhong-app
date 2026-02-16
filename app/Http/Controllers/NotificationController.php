<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/notifications - ดู notifications ทั้งหมด
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->with('product:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    // GET /api/notifications/unread - ดูเฉพาะที่ยังไม่ได้อ่าน
    public function unread(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->with('product:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    // PATCH /api/notifications/{id}/read - ทำเครื่องหมายว่าอ่านแล้ว
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }

    // PATCH /api/notifications/read-all - ทำเครื่องหมายว่าอ่านทั้งหมด
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }
}