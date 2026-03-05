<?php

namespace App\Http\Controllers;

use App\Models\Machinery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class FeedController extends Controller
{
    public function metaCatalogFeed()
    {
        // Get all machinery with categories and images
        $machineries = Machinery::with(['category', 'images' => function ($q) {
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
            'Listing Type',
            'Year'
        ];

        $callback = function () use ($machineries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $frontendUrl = 'https://stiopa-equipment.com';
            $frontendUrl = rtrim($frontendUrl, '/');

            foreach ($machineries as $machinery) {
                $availability = ($machinery->status == '1') ? 'in stock' : 'out of stock';

                $firstImageUrl = '';
                $firstImage = $machinery->images->first();
                if ($firstImage && $firstImage->type === 'image') {
                    $firstImageUrl = asset('uploads/machinery/images/' . $firstImage->image_path);
                }

                $year = $machinery->year ?? '';
                $make = $machinery->make ?? '';
                $model = $machinery->model ?? '';
                $title = trim("$year $make $model");

                $categoryName = $machinery->category ? $machinery->category->category_name : '';
                $productType = $categoryName && $make ? "$categoryName > $make" : $categoryName;
                $customLabel1 = $machinery->is_purchase ? 'buy_now' : 'auction';

                $priceVal = $machinery->buy_now_price && $machinery->buy_now_price > 0
                    ? $machinery->buy_now_price
                    : $machinery->bid_start_price;
                $price = number_format((float) $priceVal, 2, '.', '') . ' USD';

                $description = $machinery->description ?? '';

                $description = str_ireplace(['<br>', '<br/>', '<p>', '</p>'], ' ', $description);
                $description = strip_tags($description);

                $description = preg_replace("/\r|\n/", ' ', $description);
                $description = preg_replace('/\s+/', ' ', $description);
                $description = trim($description);

                $condition = strtolower($machinery->condition);

                $categorySlug = \Illuminate\Support\Str::slug($categoryName);
                $makeSlug = \Illuminate\Support\Str::slug($make);
                $modelSlug = \Illuminate\Support\Str::slug($model);

                $pathSegments = array_filter([
                    'inventory',
                    $categorySlug,
                    $makeSlug,
                    $modelSlug,
                    $machinery->auction_id
                ]);
                $link = $frontendUrl . '/' . implode('/', $pathSegments);

                $row = [
                    $machinery->id,
                    $title,
                    $description,
                    $availability,
                    $condition,
                    $price,
                    $link,
                    $firstImageUrl,
                    $make,
                    $productType,
                    $categoryName,
                    $customLabel1,
                    $year
                ];

                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
