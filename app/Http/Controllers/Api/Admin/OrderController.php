<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChangeMail;
use App\Models\Order;
use App\Services\SMTP2GOService;
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

            $perPage   = $request->input('per_page', 10);
            $page      = $request->input('page', 1);
            $sortBy    = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'order_id', 'machinery_id', 'user_id', 'price', 'delivery_status', 'created_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array($sortOrder, $allowedSortOrders)) {
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

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhere('phone_no', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('machinery', function ($q) use ($search) {
                            $q->where('id', 'LIKE', "%{$search}%");
                        });
                });
            }

            $orders = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $ordersWithFormattedData = $orders->getCollection()->map(function ($order) {
                $order->user_full_name = $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'N/A';
                $order->phone_no       = $order->user ? $order->user->phone_no : 'N/A';
                $order->order_date     = $order->purchase_date->format('M d, Y h:i A');
                $order->order_amount   = $order->price;
                $order->status         = $order->delivery_status_text;
                $order->status_code    = $order->delivery_status;
                $order->invoice_url    = $order->invoice_url;
                $order->contract_url   = $order->contract_url;

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
            'order_id' => 'required|exists:orders,id',
            'status'   => 'required|integer|between:0,5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $order = Order::with(['user', 'machinery'])->find($request->order_id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $order->delivery_status = $request->status;

            switch ($request->status) {
                case 1:$order->process_date = now();
                    break;
                case 2:$order->shipped_date = now();
                    break;
                case 3:$order->in_transit_date = now();
                    break;
                case 4:$order->delivered_date = now();
                    break;
                case 5:$order->cancelled_date = now();
                    break;
            }

            $order->save();

            if ($request->status == 1 && $order->machinery) {
                $order->machinery->update(['status' => 2]);
            }

            if ($order->user && $order->machinery) {
                try {
                    $mail = new OrderStatusChangeMail(
                        $order->user,
                        $order,
                        $order->machinery,
                        $request->status
                    );

                    $smtp2goService = new SMTP2GOService();
                    $htmlContent    = $mail->renderHtmlContent();

                    $attachments = [];

                    if ($request->status == 1) {
                        $invoice = $order->invoice;
                        if ($invoice && file_exists(public_path($invoice->image_path))) {
                            $attachments[] = [
                                'path' => public_path($invoice->image_path),
                                'name' => 'Invoice-' . $order->order_id . '.pdf',
                                'type' => 'application/pdf',
                            ];
                        }

                        $contract = $order->contract;
                        if ($contract && file_exists(public_path($contract->image_path))) {
                            $attachments[] = [
                                'path' => public_path($contract->image_path),
                                'name' => 'Contract-' . $order->order_id . '.pdf',
                                'type' => 'application/pdf',
                            ];
                        }
                    }

                    $smtp2goService->sendEmail(
                        $order->user->email,
                        $mail->getSubject(),
                        $htmlContent,
                        $attachments
                    );

                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order status updated but failed to send email notification.',
                    ], 200);
                }
            }

            $statusMessages = [
                0 => 'Order status updated to Pending.',
                1 => 'Order moved to processing and invoice sent.',
                2 => 'Order shipped successfully.',
                3 => 'Order is in transit.',
                4 => 'Order delivered successfully.',
                5 => 'Order cancelled successfully.',
            ];

            return response()->json([
                'success' => true,
                'message' => $statusMessages[$request->status] ?? 'Order status updated.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
