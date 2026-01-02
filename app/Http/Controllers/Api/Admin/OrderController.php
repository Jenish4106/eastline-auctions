<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'order_id', 'machinery_id', 'user_id', 'price', 'delivery_status', 'created_at'];
            $allowedSortOrders = ['asc', 'desc'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Order::with(['user:id,first_name,last_name,phone_no', 'machinery:id'])->select([
                'id',
                'order_id',
                'machinery_id',
                'user_id',
                'price',
                'delivery_status',
                'purchase_date',
            ]);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('order_id', 'LIKE', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('phone_no', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('machinery', function($q) use ($search) {
                          $q->where('id', 'LIKE', "%{$search}%");
                      });
                });
            }

            $orders = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $ordersWithFormattedData = $orders->getCollection()->map(function ($order) {
                $order->user_full_name = $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'N/A';
                $order->phone_no = $order->user ? $order->user->phone_no : 'N/A';
                $order->order_date = $order->purchase_date->format('M d, Y h:i A');
                $order->order_amount = $order->price;
                $order->status = $order->delivery_status_text;
                $order->status_code = $order->delivery_status;

                unset($order->user);
                
                return $order;
            });

            if ($ordersWithFormattedData->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'No orders found',
                ], 200);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Orders retrieved successfully',
                'data'       => $ordersWithFormattedData->makeHidden(['updated_at']),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                    'from'         => $orders->firstItem(),
                    'to'           => $orders->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
    
    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'status' => 'required|integer|between:0,4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }
        
        try {
            $order = Order::where('order_id', $request->order_id)->first();
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }
            
            $order->delivery_status = $request->status;
            
            switch($request->status) {
                case 0:
                    $order->process_date = now();
                    break;
                case 1: 
                    $order->shipped_date = now();
                    break;
                case 2: 
                    $order->in_transit_date = now();
                    break;
                case 3: 
                    $order->delivered_date = now();
                    break;
                case 4: 
                    $order->cancelled_date = now();
                    break;
            }
            
            $order->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'delivery_status' => $order->delivery_status,
                    'delivery_status_text' => $order->delivery_status_text,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
