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
        $validator = Validator::make($request->all(), [
            'order_id'      => 'required|integer|exists:orders,id',
            'tracking_date' => 'required|date_format:Y-m-d',
            'city'          => 'required|string|max:150',
            'status'        => 'required|string|max:100',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => null,
            ], 422);
        }

        try {
            // Verify order exists (global scope already filters soft-deleted)
            $order = Order::find($request->order_id);
            if (!$order) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Order not found.',
                    'data'    => null,
                ], 404);
            }

            $tracking = OrderTracking::create([
                'order_id'      => $request->order_id,
                'tracking_date' => $request->tracking_date,
                'city'          => trim($request->city),
                'status'        => trim($request->status),
                'lat'           => $request->lat,
                'lng'           => $request->lng,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Tracking entry added successfully.',
                'data'    => $this->formatEntry($tracking),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
                'data'    => null,
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

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'required|integer|exists:order_tracking,id',
            'tracking_date' => 'required|date_format:Y-m-d',
            'city'          => 'required|string|max:150',
            'status'        => 'required|string|max:100',
            'lat'           => 'nullable|numeric',
            'lng'           => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => null,
            ], 422);
        }

        try {
            $tracking = OrderTracking::find($request->id);
            if (!$tracking) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tracking entry not found.',
                    'data'    => null,
                ], 404);
            }

            $tracking->update([
                'tracking_date' => $request->tracking_date,
                'city'          => trim($request->city),
                'status'        => trim($request->status),
                'lat'           => $request->lat,
                'lng'           => $request->lng,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Tracking entry updated successfully.',
                'data'    => $this->formatEntry($tracking->fresh()),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
                'data'    => null,
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:order_tracking,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'data'    => null,
            ], 422);
        }

        try {
            $tracking = OrderTracking::find($request->id);
            if (!$tracking) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tracking entry not found.',
                    'data'    => null,
                ], 404);
            }

            $tracking->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Tracking entry deleted successfully.',
                'data'    => null,
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
            'status'        => $entry->status,
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
