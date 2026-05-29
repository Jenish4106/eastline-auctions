<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $totalBidsPlaced = Bid::where('user_id', $user->id)->count();

            $activeBids = $this->getActiveBids($user->id);

            $itemsWon = Machinery::where('won_user', $user->id)
                ->whereIn('contract_status', [0, 1, 3])
                ->count();

            $itemsPurchased = Order::where('user_id', $user->id)->count();

            $recentBids = $this->getRecentBids($user->id);

            $recentBuyOrders = $this->getRecentBuyOrders($user->id);
            $latestWon = Machinery::where('won_user', $user->id)
                ->whereIn('contract_status', [0, 1, 3])
                ->latest('bid_won_date')
                ->first();
            $isCheckout = $latestWon && $latestWon->contract_status == 3;
            $pdfUrl = null;
            $orderStatusText = null;

            if ($latestWon) {
                $latestWonOrder = Order::where('machinery_id', $latestWon->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($latestWonOrder) {
                    $orderStatusText = $latestWonOrder->delivery_status;
                    
                    if (in_array($latestWon->contract_status, [1, 3])) {
                        $invoiceFile = MachineryFileManager::where('machinery_id', $latestWon->id)
                            ->where('order_id', $latestWonOrder->id)
                            ->where('type', 'invoice')
                            ->first();
                        
                        if ($invoiceFile) {
                            $pdfUrl = asset($invoiceFile->image_path);
                        }
                    }
                }
            }


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
                'is_won' => $latestWon ? 1 : 0,
                'is_checkout' => $isCheckout,
                'machinery_details' => $latestWon ? [
                    'id' => $latestWon->id,
                    'name' => trim($latestWon->year . ' ' . $latestWon->make . ' ' . $latestWon->model),
                    'pdf_url' => $pdfUrl,
                    'order_status' => $orderStatusText,
                ] : null,
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
            ->select('machinery_id', 'amount', 'auction_id')
            ->with(['machinery' => function($query) {
                $query->select('id', 'bid_end_time', 'bid_status', 'won_user', 'auction_id');
            }])
            ->get();

        $activeBidCount = 0;

        foreach ($userBids as $bid) {
            if (
                !$bid->machinery ||
                $bid->machinery->bid_end_time < now() ||
                (string) $bid->machinery->bid_status === '3'
            ) {
                continue;
            }

            if ($bid->auction_id !== $bid->machinery->auction_id) {
                continue;
            }

            $highestBid = Bid::where('machinery_id', $bid->machinery_id)
                ->where('auction_id', $bid->machinery->auction_id)
                ->max('amount');

            if ($bid->amount == $highestBid) {
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
                    'auction_id' => $bid->auction_id,
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
            }, 'user:id,first_name,last_name,phone_no'])
            ->orderBy('purchase_date', 'desc')
            ->limit(5)
            ->get();

        $recentBuyOrders = [];

        foreach ($orders as $order) {
            $year = $order->machinery->year ?? '';
            $make = $order->machinery->make ?? '';
            $model = $order->machinery->model ?? '';
            $machineryName = trim("$year $make $model");

            $recentBuyOrders[] = [
                'order_id' => $order->order_id,
                'machinery_name' => $machineryName,
                'user_name' => ($order->user->first_name ?? '') . ' ' . ($order->user->last_name ?? ''),
                'phone_no' => $order->user->phone_no ?? 'N/A',
                'order_date' => $order->purchase_date ? $order->purchase_date->format('Y-m-d H:i:s') : null,
                'type' => $order->type,
                'type_text' => $order->type == 1 ? 'Checkout' : 'Bidding',
                'amount' => $order->price,
                'status' => $order->delivery_status_text,
                'invoice_url' => $order->invoice_url,
                'contract_url' => $order->contract_url,
            ];
        }

        return $recentBuyOrders;
    }
}
