<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Machinery;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();

        $totalMachinery = Machinery::count();

        $pendingLicenseUsers = User::where('is_license', 0)->count();

        $recentBids = Bid::with(['machinery:id,auction_id,make,model,year,bid_end_time'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($bid) {
                $year = $bid->machinery->year ?? '';
                $make = $bid->machinery->make ?? '';
                $model = $bid->machinery->model ?? '';
                $machineryName = trim("$year $make $model");

                $totalBids = Bid::where('machinery_id', $bid->machinery_id)->count();

                return [
                    'auction_id' => $bid->machinery->auction_id,
                    'machinery_name' => $machineryName,
                    'total_bids' => $totalBids,
                    'bid_end_time' => $bid->machinery->bid_end_time->format('Y-m-d H:i:s') ?? null
                ];
            });

        $recentWonUsers = Machinery::with(['wonUser:id,first_name,last_name', 'category:id,category_name'])
            ->whereNotNull('won_user')
            ->whereNotNull('bid_won_date')
            ->orderBy('bid_won_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($machinery) {
                $year = $machinery->year ?? '';
                $make = $machinery->make ?? '';
                $model = $machinery->model ?? '';
                $machineryName = trim("$year $make $model");

                return [
                    'machinery_name' => $machineryName,
                    'won_user_name' => $machinery->wonUser->first_name . ' ' . $machinery->wonUser->last_name ?? 'N/A',
                    'won_bid_price' => $machinery->buy_now_price ?? 0
                ];
            });

        $recentUsers = User::select('id', 'first_name', 'last_name', 'email', 'phone_no', 'created_at', 'is_license', 'status')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                switch ($user->is_license) {
                    case 1:
                        $licenseStatus = 'Approved';
                        break;
                    case 2:
                        $licenseStatus = 'Declined';
                        break;
                    case 0:
                    default:
                        $licenseStatus = 'Pending';
                }

                switch ($user->status) {
                    case 1:
                        $userStatus = 'Active';
                        break;
                    case 2:
                        $userStatus = 'Blocked';
                        break;
                    default:
                        $userStatus = 'Inactive';
                }

                return [
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone_no' => $user->phone_no,
                    'registration_date' => $user->created_at,
                    'license_status' => $licenseStatus,
                    'user_status' => $userStatus
                ];
            });

        $recentOrders = Order::with('user:id,first_name,last_name,phone_no')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'user_name' => ($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? ''),
                    'phone_no' => $order->user->phone_no ?? 'N/A',
                    'order_date' => $order->purchase_date->format('Y-m-d H:i:s') ?? null,
                    'amount' => $order->price,
                    'status' => $order->delivery_status_text,
                    'invoice_url' => $order->invoice_url,
                    'contract_url' => $order->contract_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_machinery' => $totalMachinery,
                'pending_license_users' => $pendingLicenseUsers,
                'recent_bids' => $recentBids,
                'recent_won_users' => $recentWonUsers,
                'recent_users' => $recentUsers,
                'recent_orders' => $recentOrders
            ]
        ], 200);
    }
}
