<?php

namespace App\Http\Controllers;

use App\Models\Machinery;
use App\Services\FileResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    public function metaCatalogFeed()
    {
        // Fetch all machinery with category and bids
        $machineries = Machinery::with(['category', 'bids', 'images' => function ($q) {
            $q->where('type', 'image')->orderBy('id');
        }])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="meta-catalog-feed.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $columns = [
            'ID',
            'Title',
            'Description',
            'Availability',
            'Condition',
            'Price',
            'Link',
            'Image Link',
            'Brand',
            'Product Type',
            'Category',
            'Year',
            'Time Left',
            'Current Bid'
        ];

        $callback = function () use ($machineries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $frontendUrl = env('FRONTENT_URL', 'https://eastlineauctions.com');
            $frontendUrl = rtrim($frontendUrl, '/');

            $now = Carbon::now();

            foreach ($machineries as $machinery) {
                // Calculate Highest / Current Bid
                $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
                $currentBidVal = $highestBid ?? ($machinery->buy_now_price > 0 ? $machinery->buy_now_price : $machinery->bid_start_price);
                $formattedBidPrice = '$' . number_format((float)$currentBidVal, 0);

                // Calculate Availability & Dates
                $startTime = !empty($machinery->bid_start_time) ? Carbon::parse($machinery->bid_start_time) : null;
                $endTime = !empty($machinery->bid_end_time) ? Carbon::parse($machinery->bid_end_time) : null;

                $hasStarted = !$startTime || $startTime->lte($now);
                $isSoldOrCompleted = in_array((string)$machinery->bid_status, ['2', '3', 'sold'], true) || !empty($machinery->won_user);
                $isActive = ($machinery->status == 1) && $hasStarted && !$isSoldOrCompleted && ($endTime && $endTime->gt($now));

                $availability = $isActive ? 'in stock' : 'out of stock';

                // Product Title
                $year = $machinery->year ?? '';
                $make = $machinery->make ?? '';
                $model = $machinery->model ?? '';
                $title = trim("$year $make $model");
                if (empty($title)) {
                    $title = "Machinery #" . ($machinery->auction_id ?? $machinery->id);
                }

                // Time Left calculation
                $timeLeftStr = '';
                if ($startTime && $startTime->gt($now)) {
                    $timeLeftStr = "Auction Starts Soon";
                } elseif ($endTime && $endTime->gt($now) && !$isSoldOrCompleted) {
                    $diffInSeconds = $now->diffInSeconds($endTime, false);
                    $days = (int)floor($diffInSeconds / 86400);
                    $remainingSeconds = $diffInSeconds % 86400;
                    $hours = (int)floor($remainingSeconds / 3600);
                    $minutes = (int)floor(($remainingSeconds % 3600) / 60);

                    if ($days > 0) {
                        $timeLeftStr = $days === 1 ? "1 day left to bid" : "{$days} days left to bid";
                    } elseif ($hours > 0) {
                        $timeLeftStr = $hours === 1 ? "1 hour left to bid" : "{$hours} hours left to bid";
                    } else {
                        $minVal = max(1, $minutes);
                        $timeLeftStr = $minVal === 1 ? "1 minute left to bid" : "{$minVal} minutes left to bid";
                    }
                } elseif ($endTime && $endTime->lte($now)) {
                    $timeLeftStr = "Auction Ended";
                } else {
                    $timeLeftStr = "N/A";
                }

                // Price field format: 10800.00 USD
                $price = number_format((float) $currentBidVal, 2, '.', '') . ' USD';

                // Brand
                $brand = !empty($machinery->make) ? $machinery->make : 'Eastline Auctions';

                // Category & Product Type
                $categoryName = $machinery->category ? $machinery->category->category_name : '';
                $productType = ($categoryName && !empty($machinery->make)) ? "{$categoryName} > {$machinery->make}" : $categoryName;

                // Description
                $description = $machinery->description ?? '';
                $description = str_ireplace(['<br>', '<br/>', '<p>', '</p>'], ' ', $description);
                $description = strip_tags($description);
                $description = preg_replace("/\r|\n/", ' ', $description);
                $description = preg_replace('/\s+/', ' ', $description);
                $description = trim($description);
                if (empty($description)) {
                    $description = $title;
                }

                $categorySlug = !empty($categoryName) ? Str::slug($categoryName) : 'all';
                $makeSlug = !empty($machinery->make) ? Str::slug($machinery->make) : 'all';
                $modelSlug = !empty($machinery->model) ? Str::slug($machinery->model) : 'all';
                $auctionId = !empty($machinery->auction_id) ? $machinery->auction_id : $machinery->id;

                $machineryUrl = "{$frontendUrl}/inventory/{$categorySlug}/{$makeSlug}/{$modelSlug}/{$auctionId}";

                $firstImage = $machinery->images->first();
                $imageLink = FileResolverService::resolveMachineryImageUrl($firstImage ? $firstImage->image_path : null);

                $customLabel1 = $machinery->buy_now_price > 0 ? 'buy_now' : 'auction';

                fputcsv($file, [
                    $auctionId,
                    $title,
                    $description,
                    $availability,
                    $machinery->condition ?? 'used',
                    $price,
                    $machineryUrl,
                    $imageLink,
                    $brand,
                    $productType,
                    $categoryName,
                    $year,
                    $timeLeftStr,
                    $formattedBidPrice
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
