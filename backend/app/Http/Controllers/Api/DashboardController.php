<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Borrowing;
use App\Models\Cupboard;
use App\Models\Item;
use App\Models\Place;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'stats' => [
                'total_items'        => Item::count(),
                'total_cupboards'    => Cupboard::count(),
                'total_places'       => Place::count(),
                'total_users'        => User::count(),
                'items_in_store'     => Item::where('status', 'in_store')->count(),
                'items_borrowed'     => Item::where('status', 'borrowed')->count(),
                'items_damaged'      => Item::where('status', 'damaged')->count(),
                'items_missing'      => Item::where('status', 'missing')->count(),
                'active_borrowings'  => Borrowing::where('status', 'active')->count(),
                'overdue_borrowings' => Borrowing::where('status', 'active')
                    ->where('expected_return_date', '<', now())->count(),
            ],
            'recent_activity' => AuditLog::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'recent_borrowings' => Borrowing::with(['item:id,name,code', 'creator:id,name'])
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    }
}
