<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Settings;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

            if($machinery->bid_status == 0){
                $machinery->update([
                    'bid_status' => 1
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bid placed successfully.',
                'bid' => $bid,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getMachineryWithBids(Request $request)
    {
        try {
            $user = auth('api')->user();

            $machineries = Machinery::whereHas('bids')
                ->with(['images', 'bids.user'])
                ->get();

            $result = [];

            foreach ($machineries as $machinery) {
                $bids = $machinery->bids;
                $highestBid = $bids->max('amount');
                $lastBid = $highestBid ?: $machinery->bid_start_price;

                $currentUserBids = $bids->where('user_id', $user->id);
                $currentUserHighestBid = $currentUserBids->max('amount');
                
                $status = 'Active';
                
                if ($machinery->bid_status === 'sold' || $machinery->won_user) {
                    if ($machinery->won_user == $user->id) {
                        $status = 'won';
                    } else {
                        $status = 'sold';
                    }
                } else {
                    if ($currentUserBids->count() > 0) {
                        if ($currentUserHighestBid >= $lastBid) {
                            $status = 'Active';
                        } else {
                            $status = 'Outbid';
                        }
                    } else {
                        $status = 'Active';
                    }
                }

                $firstImage = $machinery->images->firstWhere('type', 'image');

                $result[] = [
                    'id' => $machinery->id,
                    'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                    'bid_start_price' => $machinery->bid_start_price,
                    'last_bid' => $lastBid,
                    'bid_end_time' => $machinery->bid_end_time,
                    'status' => $status,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
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
        $machineryId = $request->machineryId;
        try {
            $user = auth('api')->user();

            $machinery = Machinery::with(['images', 'bids.user'])->find($machineryId);
            
            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $bids = $machinery->bids;
            
            $highestBid = $bids->max('amount');
            $lastBid = $highestBid ?: $machinery->bid_start_price;
            
            $currentUserBids = $bids->where('user_id', $user->id);
            $currentUserHighestBid = $currentUserBids->max('amount');
            
            $status = 'Active';
            
            if ($machinery->bid_status === 'sold' || $machinery->won_user) {
                if ($machinery->won_user == $user->id) {
                    $status = 'won';
                } else {
                    $status = 'sold';
                }
            } else {
                if ($currentUserBids->count() > 0) {
                    if ($currentUserHighestBid >= $lastBid) {
                        $status = 'Active';
                    } else {
                        $status = 'Outbid';
                    }
                } else {
                    $status = 'Active';
                }
            }

            $firstImage = $machinery->images->firstWhere('type', 'image');
            
            $machineryDetails = [
                'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'bid_end_time' => $machinery->bid_end_time,
                'start_bid_price' => $machinery->bid_start_price,
                'my_bid' => $currentUserHighestBid,
                'user_full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'status' => $status,
                'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
            ];

            $sortedBids = $bids->sortByDesc('amount');
            
            $biddingDetails = [];
            foreach ($sortedBids as $bid) {
                $biddingDetails[] = [
                    'user_full_name' => trim(($bid->user->first_name ?? '') . ' ' . ($bid->user->last_name ?? '')),
                    'amount' => $bid->amount,
                    'bid_date_time' => $bid->created_at,
                    'my_bid' => $bid->user_id == $user->id,
                ];
            }

            return response()->json([
                'success' => true,
                'machinery_details' => $machineryDetails,
                'bidding_details' => $biddingDetails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getUserWonBids(Request $request)
    {
        try {
            $user = auth('api')->user();

            $wonMachinery = Machinery::where('won_user', $user->id)
                ->with(['images', 'category'])
                ->get();

            $result = [];

            foreach ($wonMachinery as $machinery) {
                $highestBid = $machinery->bids()->max('amount');
                
                $firstImage = $machinery->images->firstWhere('type', 'image');
                
                $contractStatusMap = [
                    0 => 'Pending',
                    1 => 'Approved',
                    3 => 'Signed',
                    4 => 'Rejected',
                ];
                
                $result[] = [
                    'id' => $machinery->id,
                    'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                    'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                    'won_bid_amount' => $highestBid,
                    'won_date' => $machinery->bid_won_date,
                    'contract_status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getSingleWonBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machineryId' => 'required|exists:machinery,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }
        
        try {
            $user = auth('api')->user();

            $machineryId = $request->machineryId;
            
            $machinery = Machinery::with(['category', 'bids'])->find($machineryId);
            
            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }
            
            if ($machinery->won_user != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this contract',
                ], 403);
            }
            
            $highestBid = $machinery->bids()->max('amount');
            
            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];
            
            $winningUser = User::find($machinery->won_user);
            
            $highestBidModel = $machinery->bids()->orderBy('amount', 'desc')->first();
            
            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $winningUser,
                'companyInfo' => [
                    'name' => Settings::get('company_name',),
                    'address' => Settings::get('address'),
                    'phone' => Settings::get('phone_no'),
                    'email' => Settings::get('email'),
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];
            
            $contractHtml = View::make('pdf.contract', $contractDataView)->render();
            
            $contractData = [
                'id' => $machinery->id,
                'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                'start_bid_price' => $machinery->bid_start_price,
                'won_bid_amount' => $highestBid,
                'status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                'contract_html' => $contractHtml,
            ];

            return response()->json([
                'success' => true,
                'data' => $contractData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function addSignatureToContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'sign_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }
        
        try {
            $user = auth('api')->user();

            $machineryId = $request->machinery_id;
            
            $machinery = Machinery::with(['category', 'bids'])->find($machineryId);
            
            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }
            
            if ($machinery->won_user != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to sign this contract',
                ], 403);
            }
            
            // Process the signature image
            $signatureImage = $request->file('sign_photo');
            
            // Create directory if it doesn't exist in the public/uploads folder
            $signatureDirectory = public_path('uploads/signatures');
            if (!File::exists($signatureDirectory)) {
                File::makeDirectory($signatureDirectory, 0755, true);
            }
            
            // Store signature in public/uploads/signatures
            $signatureFileName = time() . '_' . $signatureImage->getClientOriginalName();
            $signaturePath = 'uploads/signatures/' . $signatureFileName;
            $signatureImage->move(public_path('uploads/signatures'), $signatureFileName);
            
            $highestBid = $machinery->bids()->max('amount');
            
            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];
            
            $winningUser = User::find($machinery->won_user);
            
            $highestBidModel = $machinery->bids()->orderBy('amount', 'desc')->first();
            
            // Add signature to the contract data
            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $winningUser,
                'signaturePath' => $signaturePath, // This will be used for the PDF
                'absoluteSignaturePath' => public_path($signaturePath), // Absolute path for PDF generation
                'companyInfo' => [
                    'name' => Settings::get('company_name',),
                    'address' => Settings::get('address'),
                    'phone' => Settings::get('phone_no'),
                    'email' => Settings::get('email'),
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];
            
            // Generate PDF with signature
            $pdf = Pdf::loadView('pdf.contract', $contractDataView);
            $pdfContent = $pdf->output();
            
            // Ensure directory exists and store the PDF in the machinery_files table
            $pdfFileName = 'contract_' . $machineryId . '_' . time() . '.pdf';
            $pdfPath = 'machinery_files/' . $pdfFileName;
            
            // Create directory if it doesn't exist in the public/uploads folder
            $publicDirectory = public_path('uploads/machinery_files');
            if (!File::exists($publicDirectory)) {
                File::makeDirectory($publicDirectory, 0755, true);
            }
            
            // Save PDF to public/uploads/machinery_files directory
            $fullPath = public_path('uploads/machinery_files/' . $pdfFileName);
            file_put_contents($fullPath, $pdfContent);
            
            // Store relative path in database
            $pdfPath = 'uploads/machinery_files/' . $pdfFileName;
            
            // Save to MachineryFileManager
            $fileManager = MachineryFileManager::create([
                'machinery_id' => $machineryId,
                'image_path' => $pdfPath,
                'type' => 'contract_pdf',
            ]);
            
            // Update machinery contract status and path to 'Signed' (3)
            $machinery->update([
                'contract_status' => 3,
                'contract_path' => $pdfPath,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Contract signed and PDF generated successfully',
                'data' => [
                    'contract_file_id' => $fileManager->id,
                    'contract_file_path' => asset($pdfPath),
                    'signature_path' => asset($signaturePath),
                    'machinery_id' => $machineryId,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
