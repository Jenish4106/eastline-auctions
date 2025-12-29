<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    
    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
            'status' => 'required|integer|between:0,3', // 0: Process, 1: In Transit, 2: Delivered, 3: Cancelled
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
            
            // Update the status
            $order->delivery_status = $request->status;
            
            // Set appropriate date based on status
            switch($request->status) {
                case 0: // Process
                    $order->process_date = now();
                    break;
                case 1: // In Transit
                    $order->in_transit_date = now();
                    break;
                case 2: // Delivered
                    $order->delivered_date = now();
                    break;
                case 3: // Cancelled
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
