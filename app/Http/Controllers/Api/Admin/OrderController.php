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

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'order_id', 'machinery_id', 'user_id', 'type', 'price', 'delivery_status', 'created_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Order::with(['user:id,first_name,last_name,phone_no', 'machinery:id,make,model,year'])->select([
                'id',
                'order_id',
                'machinery_id',
                'user_id',
                'type',
                'price',
                'delivery_status',
                'purchase_date',
                'payment_slip_path',
                'payment_slip_status',
            ]);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('order_id', 'LIKE', "%{$search}%")
                        ->orWhere('price', 'LIKE', "%{$search}%")
                        ->orWhere('billing_city', 'LIKE', "%{$search}%")
                        ->orWhere('billing_country', 'LIKE', "%{$search}%")
                        ->orWhere('billing_zip', 'LIKE', "%{$search}%")
                        ->orWhere('shipping_city', 'LIKE', "%{$search}%")
                        ->orWhere('shipping_country', 'LIKE', "%{$search}%")
                        ->orWhere('shipping_zip', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q
                                ->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"])
                                ->orWhere('phone_no', 'LIKE', "%{$search}%")
                                ->orWhere('email', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('machinery', function ($q) use ($search) {
                            $q
                                ->where('id', 'LIKE', "%{$search}%")
                                ->orWhere('auction_id', 'LIKE', "%{$search}%")
                                ->orWhere('make', 'LIKE', "%{$search}%")
                                ->orWhere('model', 'LIKE', "%{$search}%")
                                ->orWhere('year', 'LIKE', "%{$search}%")
                                ->orWhereRaw("CONCAT_WS(' ', year, make, model) LIKE ?", ["%{$search}%"]);
                        });

                    // Delivery Status search
                    $deliveryStatusMap = [
                        'order submitted' => 0,
                        'sales agreement' => 1,
                        'awaiting invoice' => 2,
                        'settle payment' => 3,
                        'payment confirmed' => 4,
                        'processing' => 5,
                        'shipping started' => 6,
                        'in transit' => 7,
                        'delivered' => 8,
                        'cancelled' => 9,
                    ];
                    foreach ($deliveryStatusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('delivery_status', $value);
                        }
                    }

                    // Payment Slip Status search
                    $paymentStatusMap = [
                        'pending' => 0,
                        'approve' => 1,
                        'decline' => 2,
                    ];
                    foreach ($paymentStatusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('payment_slip_status', $value);
                        }
                    }
                });
            }

            $orders = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $ordersWithFormattedData = $orders->getCollection()->map(function ($order) {
                $order->user_full_name = $order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'N/A';
                $order->phone_no = $order->user ? $order->user->phone_no : 'N/A';
                $order->machinery_name = $order->machinery ? $order->machinery->year . ' ' . $order->machinery->make . ' ' . $order->machinery->model : 'N/A';
                $order->order_date = $order->purchase_date->format('M d, Y h:i A');
                $order->order_amount = $order->price;
                $order->type = $order->type;
                $order->type_text = $order->type == 1 ? 'Checkout' : 'Bidding';
                $order->status = $order->delivery_status_text;
                $order->status_code = $order->delivery_status;
                $order->invoice_url = $order->invoice_url;
                $order->contract_url = $order->contract_url;
                $order->payment_slip_url = $order->payment_slip_url;
                $order->payment_slip_status = $order->payment_slip_status;
                $order->payment_slip_status_text = $order->payment_slip_status_text;

                unset($order->user);
                unset($order->machinery);

                return $order;
            });

            if ($ordersWithFormattedData->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No orders found',
                ], 200);
            }

            return response()->json([
                'status' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $ordersWithFormattedData->makeHidden(['updated_at']),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /** Update order status */

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|integer|between:0,9',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $order = Order::with(['user', 'machinery'])->find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $order->delivery_status = $request->status;

            switch ($request->status) {
                case 1:
                    $order->sales_agreement_date = now();
                    break;
                case 2:
                    $order->awaiting_invoice_date = now();
                    break;
                case 3:
                    $order->settle_payment_date = now();
                    break;
                case 4:
                    $order->confirmation_date = now();
                    break;
                case 5:
                    $order->process_date = now();
                    break;
                case 6:
                    $order->shipped_date = now();
                    break;
                case 7:
                    $order->in_transit_date = now();
                    break;
                case 8:
                    $order->delivered_date = now();
                    break;
                case 9:
                    $order->cancelled_date = now();
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
                    $htmlContent = $mail->renderHtmlContent();

                    $attachments = [];

                    if ($request->status == 1) {
                        $contract = $order->contract;
                        if ($contract && file_exists(public_path($contract->image_path))) {
                            $attachments[] = [
                                'path' => public_path($contract->image_path),
                                'name' => 'Contract-' . $order->order_id . '.pdf',
                                'type' => 'application/pdf',
                            ];
                        }
                    } elseif ($request->status == 3) {
                        $invoice = $order->invoice;
                        if ($invoice && file_exists(public_path($invoice->image_path))) {
                            $attachments[] = [
                                'path' => public_path($invoice->image_path),
                                'name' => 'Invoice-' . $order->order_id . '.pdf',
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
                0 => 'Order status updated to Order Submitted.',
                1 => 'Order moved to Sales Agreement.',
                2 => 'Order moved to Awaiting Invoice.',
                3 => 'Order moved to Settle Payment.',
                4 => 'Order moved to Payment Confirmed.',
                5 => 'Order moved to Processing.',
                6 => 'Order status updated to Shipping Started.',
                7 => 'Order is in transit.',
                8 => 'Order delivered successfully.',
                9 => 'Order cancelled successfully.',
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

    public function updatePaymentSlipStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|integer|in:0,1,2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $order = Order::with(['user', 'machinery', 'invoice', 'contract'])->find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $order->payment_slip_status = $request->status;
            $order->save();

            $message = 'Payment slip status updated.';

            if ($request->status == 1) {
                $order->delivery_status = 4;
                $order->confirmation_date = now();
                $order->save();

                if ($order->machinery) {
                    $order->machinery->update(['status' => 2]);
                }

                if ($order->user && $order->machinery) {
                    try {
                        $mail = new OrderStatusChangeMail(
                            $order->user,
                            $order,
                            $order->machinery,
                            4
                        );

                        $smtp2goService = new SMTP2GOService();
                        $htmlContent = $mail->renderHtmlContent();

                        $attachments = [];

                        $smtp2goService->sendEmail(
                            $order->user->email,
                            $mail->getSubject(),
                            $htmlContent,
                            $attachments
                        );
                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Something went wrong. Please try again.',
                        ], 500);
                    }
                }

                $message = 'Payment slip approved and order confirmed.';
            } elseif ($request->status == 2) {
                $message = 'Payment slip declined.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
