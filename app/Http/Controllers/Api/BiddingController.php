<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BiddingController extends Controller
{
    public function placeBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }
        
        try {
            $user = auth('api')->user();

            if ($user->is_license == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your license is not verified.',
                ], 403);
            }

            $machinery = Machinery::find($request->machinery_id);
            
            $highestBid = Bid::where('machinery_id', $request->machinery_id)
                            ->max('amount');
            
            $minAmount = $highestBid !== null ? $highestBid : $machinery->bid_start_price;
            
            if ($request->amount <= $minAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid amount must be greater than the current highest bid of ' . $minAmount,
                ], 400);
            }

            $bid = Bid::create([
                'user_id' => $user->id,
                'machinery_id' => $request->machinery_id,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bid placed successfully.',
                'bid' => $bid,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
