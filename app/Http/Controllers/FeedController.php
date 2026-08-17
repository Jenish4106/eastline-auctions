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
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'link',
            'image_link',
            'brand',
            'product_type',
            'category',
            'listing_type',
            'year'
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

                // Calculate Availability
                $endTime = $machinery->bid_end_time ? Carbon::parse($machinery->bid_end_time) : null;
                $isActive = ($machinery->status == 1) && ($machinery->bid_status == 1) && ($endTime && $endTime->gt($now));

                $availability = $isActive ? 'in stock' : 'out of stock';

                // Calculate Dynamic Title
                if ($isActive && $endTime) {
                    $diffInSeconds = $now->diffInSeconds($endTime, false);
                    if ($diffInSeconds > 0) {
                        $days = (int)floor($diffInSeconds / 86400);
                        $remainingSeconds = $diffInSeconds % 86400;
                        $hours = (int)floor($remainingSeconds / 3600);
                        $minutes = (int)floor(($remainingSeconds % 3600) / 60);

                        if ($days > 0) {
                            $timeStr = $days === 1 ? "1 day left to bid" : "{$days} days left to bid";
                        } elseif ($hours > 0) {
                            $timeStr = $hours === 1 ? "1 hour left to bid" : "{$hours} hours left to bid";
                        } else {
                            $minVal = max(1, $minutes);
                            $timeStr = $minVal === 1 ? "1 minute left to bid" : "{$minVal} minutes left to bid";
                        }

                        $title = "{$timeStr} | Current bid: {$formattedBidPrice}";
                    } else {
                        $title = "Auction Ended | Final bid: {$formattedBidPrice}";
                    }
                } else {
                    $year = $machinery->year ?? '';
                    $make = $machinery->make ?? '';
                    $model = $machinery->model ?? '';
                    $fallbackName = trim("$year $make $model");
                    if (empty($fallbackName)) {
                        $fallbackName = "Machinery #" . $machinery->id;
                    }
                    $title = "{$fallbackName} | Out of Stock";
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

                // Dynamic Image URL with cache buster parameter v= under /api
                $appUrl = rtrim(url('/'), '/');
                if (!str_ends_with($appUrl, '/api')) {
                    $appUrl .= '/api';
                }
                $versionHash = substr(md5(($machinery->updated_at ?? $now) . '_' . $currentBidVal . '_' . ($machinery->bid_end_time ?? '')), 0, 8);
                $imageLink = $appUrl . '/catalog/images/' . $machinery->id . '.jpg?v=' . $versionHash;

                $customLabel1 = $machinery->buy_now_price > 0 ? 'buy_now' : 'auction';
                $year = $machinery->year ?? '';

                fputcsv($file, [
                    $machinery->id,
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
                    $customLabel1,
                    $year
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
