<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChangeMail;
use App\Mail\ResendInvoiceMail;
use App\Models\MachineryFileManager;
use App\Models\Order;
use App\Models\Settings;
use App\Services\MailtrapService;
use App\Services\S3StorageService;
use App\Services\TwilioSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

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

            $query = Order::with(['user:id,first_name,last_name,phone_no,email', 'machinery:id,make,model,year,auction_id'])
                ->where('is_deleted', 0)
                ->whereHas('user')
                ->select([
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
                $order->order_date = $order->purchase_date ? $order->purchase_date->format('M d, Y h:i A') : null;
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
            $order = Order::with(['user', 'machinery'])
                ->whereHas('user')
                ->find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or user does not exist',
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

                    $mailtrapService = new MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $attachments = [];

                    if ($request->status == 1) {
                        $contract = $order->contract;
                        if ($contract && S3StorageService::exists($contract->image_path)) {
                            $attachments[] = [
                                'path' => $contract->image_path,
                                'name' => 'Contract-' . $order->order_id . '.pdf',
                                'type' => 'application/pdf',
                            ];
                        }
                    } elseif ($request->status == 3) {
                        $invoice = $order->invoice;
                        if ($invoice && S3StorageService::exists($invoice->image_path)) {
                            $attachments[] = [
                                'path' => $invoice->image_path,
                                'name' => 'Invoice-' . $order->order_id . '.pdf',
                                'type' => 'application/pdf',
                            ];
                        }
                    }

                    $mailtrapService->sendEmail($order->user->email, $mail->getSubject(), $htmlContent, $attachments);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order status updated but failed to send email notification.',
                    ], 200);
                }

                if ((int) $request->status === 3) {
                    (new TwilioSmsService())->sendMessage(
                        $order->user->phone_no,
                        'Thank you for your purchase with McFarland Equipment Sales & Auctions! Your invoice is now available. Please sign in to your account to review the invoice and complete payment.'
                    );
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
            $order = Order::with(['user', 'machinery', 'invoice', 'contract'])
                ->whereHas('user')
                ->find($request->order_id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or user does not exist',
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

                        $mailtrapService = new MailtrapService();
                        $htmlContent = $mail->renderHtmlContent();
                        $attachments = [];

                        $mailtrapService->sendEmail($order->user->email, $mail->getSubject(), $htmlContent, $attachments);
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

    /**
     * Soft delete order
     */
    public function delete(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $order = Order::find($request->order_id);
            $order->is_deleted = 1;
            $order->save();

            $files = MachineryFileManager::where('order_id', $order->id)
                ->whereIn('type', ['invoice', 'contract_pdf'])
                ->get();

            foreach ($files as $file) {
                S3StorageService::delete($file->image_path);
                $file->delete();
            }

            if ($order->machinery) {
                $order->machinery->status = 1;
                $order->machinery->bid_status = 0;
                $order->machinery->contract_status = 0;
                $order->machinery->won_user = null;
                $order->machinery->bid_won_date = null;
                $order->machinery->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Regenerate invoice for an order
     */
    public function regenerateInvoice(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $orderIdInput = $request->input('order_id');

            $order = Order::with(['user', 'machinery'])
                ->find($orderIdInput);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if (!$order->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order user not found',
                ], 400);
            }

            if (!$order->machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order machinery not found',
                ], 400);
            }

            $oldInvoices = MachineryFileManager::where('order_id', $order->id)
                ->where('type', 'invoice')
                ->get();

            $oldInvoiceRecord = null;
            if ($oldInvoices->isNotEmpty()) {
                $oldInvoiceRecord = $oldInvoices->first();
                if ($oldInvoices->count() > 1) {
                    foreach ($oldInvoices->slice(1) as $extraInvoice) {
                        S3StorageService::delete($extraInvoice->image_path);
                        $extraInvoice->delete();
                    }
                }
            }

            $companyName = Settings::get('company_name', 'Mcfarland Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';
            $companyLogo = Settings::get('dark_logo');

            $machinery = $order->machinery;
            $machinery->load('images');
            $firstImage = $machinery->images->firstWhere('type', 'image');
            $machineryImage = null;
            $machineryImageUrl = null;

            if ($firstImage) {
                $imagePathRel = 'uploads/machinery/images/' . ltrim($firstImage->image_path, '/');
                if (S3StorageService::exists($imagePathRel)) {
                    $machineryImage = S3StorageService::getImageAsBase64($imagePathRel);
                    $machineryImageUrl = S3StorageService::getUrl($imagePathRel);
                }
            }

            $invoiceData = [
                'order' => $order,
                'machineryImage' => $machineryImage,
                'machineryImageUrl' => $machineryImageUrl,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => $companyLogo ? S3StorageService::getImageAsBase64($companyLogo) : null,
                    'logoUrl' => $companyLogo ? S3StorageService::getUrl($companyLogo) : null,
                    'bank_name' => Settings::get('bank_name'),
                    'beneficiary_name' => Settings::get('beneficiary_name'),
                    'beneficiary_address' => Settings::get('beneficiary_address'),
                    'account_number' => Settings::get('account_number'),
                    'routing_number' => Settings::get('routing_number'),
                    'branch_address' => Settings::get('branch_address'),
                ]
            ];

            $invoicePdf = Pdf::loadView('pdf.invoice', $invoiceData);

            $invoiceFileName = 'invoice_' . $order->order_id . '.pdf';

            if ($oldInvoiceRecord && $oldInvoiceRecord->image_path) {
                S3StorageService::delete($oldInvoiceRecord->image_path);
            }

            $invoiceUpload = S3StorageService::upload($invoicePdf->output(), 'invoices', $invoiceFileName);
            $invoicePath = $invoiceUpload['relative_path'];

            if ($oldInvoiceRecord) {
                $oldInvoiceRecord->image_path = $invoicePath;
                $oldInvoiceRecord->save();
            } else {
                MachineryFileManager::create([
                    'machinery_id' => $machinery->id,
                    'order_id' => $order->id,
                    'image_path' => $invoicePath,
                    'type' => 'invoice',
                ]);
            }

            if ((int) $order->delivery_status >= 3 && (int) $order->delivery_status <= 8) {
                $order->is_regenerated = true;
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice regenerated successfully.',
                'invoice_url' => $invoiceUpload['url'],
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_id,
                    'invoice_url' => $invoiceUpload['url'],
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Resend invoice email to user
     */
    public function resendInvoiceEmail(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,order_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            $orderIdInput = $request->input('order_id');
            $order = Order::with(['user', 'machinery', 'invoice'])
                ->where('order_id', $orderIdInput)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if (!((int) $order->delivery_status >= 3 && (int) $order->delivery_status <= 8)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice can only be resent when order status is between Settle Payment and Delivered.',
                ], 400);
            }

            if (!$order->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order user not found',
                ], 400);
            }

            if (!$order->machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order machinery not found',
                ], 400);
            }

            $invoice = $order->invoice;
            if (!$invoice || !S3StorageService::exists($invoice->image_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice file not found for this order. Please generate or regenerate the invoice first.',
                ], 400);
            }

            $mail = new ResendInvoiceMail(
                $order->user,
                $order,
                $order->machinery
            );

            $mailtrapService = new MailtrapService();
            $htmlContent = $mail->renderHtmlContent();

            $attachments = [
                [
                    'path' => $invoice->image_path,
                    'name' => 'Invoice-' . $order->order_id . '.pdf',
                    'type' => 'application/pdf',
                ]
            ];

            $mailtrapService->sendEmail(
                $order->user->email,
                $mail->getSubject(),
                $htmlContent,
                $attachments
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice email sent successfully to ' . $order->user->email,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Helper to encode image path to base64
     */
    private function imageToBase64($path)
    {
        if (File::exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = @file_get_contents($path);
            if ($data !== false) {
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }
        return null;
    }
}
