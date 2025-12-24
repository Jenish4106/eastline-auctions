<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use Illuminate\Http\Request;

class BiddingController extends Controller
{
    public function getMachineryBiddingInfo(Request $request)
    {
        try {
            $machineries = Machinery::select([
                'id',
                'year',
                'make',
                'model',
                'bid_end_time',
                'bid_start_price',
                'bid_status'
            ])->withCount('bids')->get();
            
            $machineriesWithInfo = $machineries->map(function ($machinery) {
                $machinery->name = trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model);
                
                switch ($machinery->bid_status) {
                    case '1':
                    case 1:
                        $machinery->bid_status = 'active';
                        break;
                    case '2':
                    case 2:
                        $machinery->bid_status = 'sold';
                        break;
                    case '0':
                    case 0:
                        $machinery->bid_status = 'pending';
                        break;
                    default:
                        break;
                }
                
                return $machinery;
            });
            
            return response()->json([
                'success' => true,
                'data' => $machineriesWithInfo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getMachineryBiddingDetails(Request $request)
    {
        try {
            $machineryId = $request->input('machineryId');
            
            $machinery = Machinery::with('bids.user:id,first_name,last_name,email,phone_no')->where('id', $machineryId)->first();
            
            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }
            
            $highestBid = $machinery->bids->max('amount');
            
            $bidStatusText = '';
            switch ($machinery->bid_status) {
                case '0':
                case 0:
                    $bidStatusText = 'pending';
                    break;
                case '1':
                case 1:
                    $bidStatusText = 'active';
                    break;
                case '2':
                case 2:
                    $bidStatusText = 'sold';
                    break;
                default:
                    $bidStatusText = $machinery->bid_status;
                    break;
            }
            
            $machineryInfo = [
                'name' => trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model),
                'bid_start_price' => $machinery->bid_start_price,
                'highest_bid' => $highestBid,
                'bid_end_time' => $machinery->bid_end_time,
                'bid_status' => $bidStatusText,
            ];
            
            $biddingDetails = $machinery->bids->sortByDesc('amount')->map(function ($bid) use ($highestBid, $machinery) {
                $bidData = [
                    'user_full_name' => $bid->user->first_name . ' ' . $bid->user->last_name,
                    'user_email' => $bid->user->email,
                    'user_phone' => $bid->user->phone_no,
                    'bid_amount' => $bid->amount,
                    'bid_created_at' => $bid->created_at,
                    'is_highest' => $bid->amount == $highestBid,
                ];
                
                if ($machinery->bid_status == '2' || $machinery->bid_status == 2) {
                    $bidData['is_won'] = $bid->amount == $highestBid;
                }
                
                return $bidData;
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'machinery_info' => $machineryInfo,
                    'bidding_details' => $biddingDetails,
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
