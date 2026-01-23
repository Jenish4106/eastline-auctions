<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Total bids placed by the user
            $totalBidsPlaced = Bid::where('user_id', $user->id)->count();

            // Active bids where user has the highest bid
            $activeBids = $this->getActiveBids($user->id);

            // Items won by the user
            $itemsWon = Machinery::where('won_user', $user->id)->count();

            // Items purchased by the user
            $itemsPurchased = Order::where('user_id', $user->id)->count();

            // Recent bids
            $recentBids = $this->getRecentBids($user->id);

            // Recent buy orders
            $recentBuyOrders = $this->getRecentBuyOrders($user->id);

            $dashboardData = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone_no' => $user->phone_no,
                ],
                'total_bids_placed' => $totalBidsPlaced,
                'active_bids' => $activeBids,
                'items_won' => $itemsWon,
                'items_purchased' => $itemsPurchased,
                'recent_bids' => $recentBids,
                'recent_buy_orders' => $recentBuyOrders,
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    private function getActiveBids($userId)
    {
        $userBids = Bid::where('user_id', $userId)
            ->select('machinery_id', 'amount')
            ->with(['machinery' => function($query) {
                $query->select('id', 'bid_end_time', 'bid_status', 'won_user');
            }])
            ->get();

        $activeBidCount = 0;

        foreach ($userBids as $bid) {
            if ($bid->machinery && $bid->machinery->bid_end_time < now()) {
                continue;
            }

            $highestBid = Bid::where('machinery_id', $bid->machinery_id)
                ->max('amount');

            if ($bid->amount == $highestBid && $bid->machinery && $bid->machinery->bid_end_time > now()) {
                if (!$bid->machinery->won_user || $bid->machinery->won_user == $userId) {
                    $activeBidCount++;
                }
            }
        }

        return $activeBidCount;
    }

    private function getRecentBids($userId)
    {
        $bids = Bid::where('user_id', $userId)
            ->with(['machinery' => function($query) {
                $query->select('id', 'make', 'model', 'year', 'bid_end_time');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentBids = [];

        foreach ($bids as $bid) {
            if ($bid->machinery) {
                $recentBids[] = [
                    'machinery_name' => $bid->machinery->year . ' ' . $bid->machinery->make . ' ' . $bid->machinery->model,
                    'bid_amount' => $bid->amount,
                    'bid_end_time' => $bid->machinery->bid_end_time,
                ];
            }
        }

        return $recentBids;
    }

    private function getRecentBuyOrders($userId)
    {
        $orders = Order::where('user_id', $userId)
            ->with(['machinery' => function($query) {
                $query->select('id', 'make', 'model', 'year');
            }])
            ->orderBy('purchase_date', 'desc')
            ->limit(5)
            ->get();

        $recentBuyOrders = [];

        foreach ($orders as $order) {
            if ($order->machinery) {
                $recentBuyOrders[] = [
                    'machinery_name' => $order->machinery->year . ' ' . $order->machinery->make . ' ' . $order->machinery->model,
                    'price' => $order->price,
                    'purchase_date' => $order->purchase_date,
                    'status' => $order->delivery_status_text, // This uses the accessor from the Order model
                ];
            }
        }

        return $recentBuyOrders;
    }
}
