<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderTrackingController extends Controller
{
    public function store(Request $request)
    {
        $isBulk = $request->has('trackings') && is_array($request->input('trackings'));

        $rules = $isBulk ? [
            'trackings'                  => 'required|array|min:1',
            'trackings.*.order_id'       => 'required|integer|exists:orders,id',
            'trackings.*.tracking_date'  => 'required|date_format:Y-m-d',
            'trackings.*.city'           => 'required|string|max:150',
            'trackings.*.lat'            => 'required|numeric',
            'trackings.*.lng'            => 'required|numeric',
        ] : [
            'order_id'      => 'required|integer|exists:orders,id',
            'tracking_date' => 'required|date_format:Y-m-d',
            'city'          => 'required|string|max:150',
            'lat'           => 'required|numeric',
            'lng'           => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $entries = $isBulk ? $request->input('trackings') : [
                [
                    'order_id'      => $request->order_id,
                    'tracking_date' => $request->tracking_date,
                    'city'          => $request->city,
                    'lat'           => $request->lat,
                    'lng'           => $request->lng,
                ],
            ];

            $created = [];

            foreach ($entries as $entry) {
                $order = Order::find($entry['order_id']);
                if (!$order) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Order not found.',
                    ], 404);
                }

                $tracking = OrderTracking::create([
                    'order_id'      => $entry['order_id'],
                    'tracking_date' => $entry['tracking_date'],
                    'city'          => trim($entry['city']),
                    'lat'           => $entry['lat'] ?? null,
                    'lng'           => $entry['lng'] ?? null,
                ]);

                $created[] = $this->formatEntry($tracking);
            }

            return response()->json([
                'status'  => true,
                'message' => count($created) > 1
                    ? 'Tracking entries added successfully.'
                    : 'Tracking entry added successfully.',
                'data'    => $isBulk ? $created : $created[0],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => null,
            ], 422);
        }

        try {
            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Order not found.',
                    'data'    => null,
                ], 404);
            }

            $trackingEntries = OrderTracking::where('order_id', $request->order_id)
                ->orderBy('tracking_date', 'asc')
                ->get()
                ->map(fn($entry) => $this->formatEntry($entry));

            return response()->json([
                'status'  => true,
                'message' => $trackingEntries->isEmpty()
                    ? 'No tracking entries found for this order.'
                    : 'Tracking entries retrieved successfully.',
                'data'    => $trackingEntries,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
                'data'    => null,
            ], 500);
        }
    }

    private function formatEntry(OrderTracking $entry): array
    {
        return [
            'id'            => $entry->id,
            'order_id'      => $entry->order_id,
            'tracking_date' => $entry->tracking_date
                ? $entry->tracking_date->format('Y-m-d H:i:s')
                : null,
            'city'          => $entry->city,
            'lat'           => $entry->lat,
            'lng'           => $entry->lng,
            'created_at'    => $entry->created_at
                ? $entry->created_at->format('Y-m-d H:i:s')
                : null,
            'updated_at'    => $entry->updated_at
                ? $entry->updated_at->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
