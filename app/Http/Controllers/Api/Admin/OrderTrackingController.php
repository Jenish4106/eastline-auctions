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

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'sometimes|required_without_all:updates,new_trackings|integer',
            'tracking_date' => 'sometimes|required|date_format:Y-m-d',
            'city'          => 'sometimes|required|string|max:150',
            'lat'           => 'sometimes|required|numeric',
            'lng'           => 'sometimes|required|numeric',
            'updates' => 'sometimes|array|min:1',
            'updates.*.id' => 'required|integer',
            'updates.*.tracking_date' => 'sometimes|required|date_format:Y-m-d',
            'updates.*.city' => 'sometimes|required|string|max:150',
            'updates.*.lat' => 'sometimes|required|numeric',
            'updates.*.lng' => 'sometimes|required|numeric',
            'new_trackings' => 'sometimes|array|min:1',
            'new_trackings.*.order_id' => 'required|integer|exists:orders,id',
            'new_trackings.*.tracking_date' => 'required|date_format:Y-m-d',
            'new_trackings.*.city' => 'required|string|max:150',
            'new_trackings.*.lat' => 'required|numeric',
            'new_trackings.*.lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $updatedEntry = null;
            $updatedEntries = [];
            $createdEntries = [];

            if ($request->has('updates') && is_array($request->input('updates'))) {
                $updates = $request->input('updates', []);

                foreach ($updates as $payload) {
                    $tracking = OrderTracking::find($payload['id'] ?? null);
                    if (!$tracking) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Tracking entry not found.',
                        ], 404);
                    }

                    if (array_key_exists('tracking_date', $payload)) {
                        $tracking->tracking_date = $payload['tracking_date'];
                    }
                    if (array_key_exists('city', $payload)) {
                        $tracking->city = trim($payload['city']);
                    }
                    if (array_key_exists('lat', $payload)) {
                        $tracking->lat = $payload['lat'];
                    }
                    if (array_key_exists('lng', $payload)) {
                        $tracking->lng = $payload['lng'];
                    }

                    $tracking->save();

                    $entry = $this->formatEntry($tracking);
                    $entry['is_update'] = true;
                    $updatedEntries[] = $entry;
                }
            }

            if ($request->filled('id')) {
                $tracking = OrderTracking::find($request->id);
                if (!$tracking) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Tracking entry not found.',
                    ], 404);
                }

                if ($request->has('tracking_date')) {
                    $tracking->tracking_date = $request->tracking_date;
                }
                if ($request->has('city')) {
                    $tracking->city = trim($request->city);
                }
                if ($request->has('lat')) {
                    $tracking->lat = $request->lat;
                }
                if ($request->has('lng')) {
                    $tracking->lng = $request->lng;
                }

                $tracking->save();

                $updatedEntry = $this->formatEntry($tracking);
                $updatedEntry['is_update'] = true;
            }

            if ($request->has('new_trackings') && is_array($request->input('new_trackings'))) {
                $entries = $request->input('new_trackings', []);

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

                    $createdEntries[] = array_merge(
                        $this->formatEntry($tracking),
                        ['is_update' => false]
                    );
                }
            }

            if ($updatedEntry !== null && empty($updatedEntries) && empty($createdEntries)) {
                return response()->json([
                    'status'  => true,
                    'message' => 'Tracking entry updated successfully.',
                    'data'    => $updatedEntry,
                ], 200);
            }

            if ($updatedEntry === null && empty($updatedEntries)) {
                return response()->json([
                    'status'  => true,
                    'message' => count($createdEntries) > 1
                        ? 'Tracking entries added successfully.'
                        : 'Tracking entry added successfully.',
                    'data'    => count($createdEntries) === 1 ? $createdEntries[0] : $createdEntries,
                ], 201);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Tracking entries updated and added successfully.',
                'data'    => [
                    'updated' => $updatedEntry,
                    'updated_list' => $updatedEntries,
                    'created' => $createdEntries,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $tracking = OrderTracking::find($request->id);
            if (!$tracking) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Tracking entry not found.',
                ], 404);
            }

            $tracking->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Tracking entry deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
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
            'is_update'     => $entry->updated_at && $entry->created_at
                ? $entry->updated_at->gt($entry->created_at)
                : false,
            'created_at'    => $entry->created_at
                ? $entry->created_at->format('Y-m-d H:i:s')
                : null,
            'updated_at'    => $entry->updated_at
                ? $entry->updated_at->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
