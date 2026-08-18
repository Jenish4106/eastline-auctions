<?php

namespace App\Http\Controllers;

use App\Models\Machinery;
use App\Services\FileResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
      'Year'
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
        $formattedBidPrice = '$' . number_format((float) $currentBidVal, 0);

        // Calculate Availability & Dates
        $startTime = !empty($machinery->bid_start_time) ? Carbon::parse($machinery->bid_start_time) : null;
        $endTime = !empty($machinery->bid_end_time) ? Carbon::parse($machinery->bid_end_time) : null;

        $hasStarted = !$startTime || $startTime->lte($now);
        $isSoldOrCompleted = in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true) || !empty($machinery->won_user);
        $isActive = ($machinery->status == 1) && $hasStarted && !$isSoldOrCompleted && ($endTime && $endTime->gt($now));

        $availability = $isActive ? 'in stock' : 'out of stock';

        // Product Title
        $year = $machinery->year ?? '';
        $make = $machinery->make ?? '';
        $model = $machinery->model ?? '';
        $title = trim("$year $make $model");
        if (empty($title)) {
          $title = 'Machinery #' . ($machinery->auction_id ?? $machinery->id);
        }

        // Time Left calculation
        $timeLeftStr = '';
        if ($startTime && $startTime->gt($now)) {
          $timeLeftStr = 'Auction Starts Soon';
        } elseif ($endTime && $endTime->gt($now) && !$isSoldOrCompleted) {
          $diffInSeconds = $now->diffInSeconds($endTime, false);
          $days = (int) floor($diffInSeconds / 86400);
          $remainingSeconds = $diffInSeconds % 86400;
          $hours = (int) floor($remainingSeconds / 3600);
          $minutes = (int) floor(($remainingSeconds % 3600) / 60);

          if ($days > 0) {
            $timeLeftStr = $days === 1 ? '1 day left to bid' : "{$days} days left to bid";
          } elseif ($hours > 0) {
            $timeLeftStr = $hours === 1 ? '1 hour left to bid' : "{$hours} hours left to bid";
          } else {
            $minVal = max(1, $minutes);
            $timeLeftStr = $minVal === 1 ? '1 minute left to bid' : "{$minVal} minutes left to bid";
          }
        } elseif ($endTime && $endTime->lte($now)) {
          $timeLeftStr = 'Auction Ended';
        } else {
          $timeLeftStr = 'N/A';
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
        $originalImageLink = FileResolverService::resolveMachineryImageUrl($firstImage ? $firstImage->image_path : null);
        $catalogImageLink = url("/catalog-image/{$auctionId}.png");
        $imageLink = $catalogImageLink;


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
          $year
        ]);

      }

      fclose($file);
    };

    return Response::stream($callback, 200, $headers);
  }

  public function catalogImage(Request $request, $id)
  {
    // Clean ID (remove file extensions like .png, .jpg, .svg)
    $cleanId = preg_replace('/\.(png|jpg|jpeg|svg)$/i', '', $id);

    $machinery = Machinery::with(['category', 'bids', 'images' => function ($q) {
      $q->where('type', 'image')->orderBy('id');
    }])
      ->where('auction_id', $cleanId)
      ->orWhere('id', $cleanId)
      ->first();

    if (!$machinery) {
      return Response::make('<svg xmlns="http://www.w3.org/2000/svg" width="1080" height="1080" viewBox="0 0 1080 1080"><rect width="1080" height="1080" fill="#0b0c0e"/><text x="540" y="540" fill="#ffffff" font-size="36" text-anchor="middle" font-family="sans-serif">Auction Item Not Found</text></svg>', 404, [
        'Content-Type' => 'image/svg+xml'
      ]);
    }

    $now = Carbon::now();

    // 1. Calculate Highest / Current Bid
    $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
    $currentBidVal = $highestBid ?? ($machinery->buy_now_price > 0 ? $machinery->buy_now_price : $machinery->bid_start_price);
    $formattedBidPrice = '$' . number_format((float) $currentBidVal, 0);

    // 2. Time Left Calculation
    $startTime = !empty($machinery->bid_start_time) ? Carbon::parse($machinery->bid_start_time) : null;
    $endTime = !empty($machinery->bid_end_time) ? Carbon::parse($machinery->bid_end_time) : null;
    $isSoldOrCompleted = in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true) || !empty($machinery->won_user);

    $timeLeftMain = '';
    $timeLeftSub = '';

    if ($startTime && $startTime->gt($now)) {
      $timeLeftMain = 'SOON';
      $timeLeftSub = 'STARTS SOON';
    } elseif ($endTime && $endTime->gt($now) && !$isSoldOrCompleted) {
      $diffInSeconds = $now->diffInSeconds($endTime, false);
      $days = (int) floor($diffInSeconds / 86400);
      $remainingSeconds = $diffInSeconds % 86400;
      $hours = (int) floor($remainingSeconds / 3600);
      $minutes = (int) floor(($remainingSeconds % 3600) / 60);

      if ($days > 0) {
        $timeLeftMain = $days === 1 ? '1 DAY' : "{$days} DAYS";
        $timeLeftSub = 'LEFT TO BID';
      } elseif ($hours > 0) {
        $timeLeftMain = $hours === 1 ? '1 HOUR' : "{$hours} HOURS";
        $timeLeftSub = 'LEFT TO BID';
      } else {
        $minVal = max(1, $minutes);
        $timeLeftMain = $minVal === 1 ? '1 MIN' : "{$minVal} MINS";
        $timeLeftSub = 'LEFT TO BID';
      }
    } elseif ($endTime && $endTime->lte($now)) {
      $timeLeftMain = 'ENDED';
      $timeLeftSub = 'AUCTION ENDED';
    } else {
      $timeLeftMain = 'LIVE';
      $timeLeftSub = 'AUCTION OPEN';
    }

    // 3. Category & Title
    $categoryName = strtoupper($machinery->category ? $machinery->category->category_name : 'EQUIPMENT');
    $year = $machinery->year ?? '';
    $make = $machinery->make ?? '';
    $model = $machinery->model ?? '';
    $title = strtoupper(trim("$year $make $model"));
    if (empty($title)) {
      $title = 'MACHINERY #' . ($machinery->auction_id ?? $machinery->id);
    }
    $auctionId = $machinery->auction_id ?? $machinery->id;

    // 4. Product Image Resolution
    $firstImage = \App\Models\MachineryFileManager::where('machinery_id', $machinery->id)
      ->where('type', 'image')
      ->orderBy('id', 'asc')
      ->first();
    if (!$firstImage && $machinery->images) {
      $firstImage = $machinery->images->first();
    }
    $imagePath = $firstImage ? $firstImage->image_path : ($machinery->main_image ?? null);
    $imageBase64 = FileResolverService::resolveMachineryImageBase64($imagePath);
    $imageUrl = FileResolverService::resolveMachineryImageUrl($imagePath);

    // Official Eastline White Logo URL
    $logoUrl = 'https://eastlineauctions.com/api/settings/1786432466_white_logo.png';

    // HTML/SVG representation matching approved template 100%
    $titleSvg = htmlspecialchars($title, ENT_XML1, 'UTF-8');
    $fullIdSvg = htmlspecialchars("ID: " . $auctionId, ENT_XML1, 'UTF-8');
    $fullCategorySvg = htmlspecialchars("CATEGORY: " . $categoryName, ENT_XML1, 'UTF-8');
    $formattedPriceSvg = htmlspecialchars($formattedBidPrice, ENT_XML1, 'UTF-8');

    $timeLeftMainSvg = htmlspecialchars($timeLeftMain, ENT_XML1, 'UTF-8');
    $timeLeftSubSvg = htmlspecialchars($timeLeftSub, ENT_XML1, 'UTF-8');
    $photoSrcSvg = !empty($imageBase64) ? $imageBase64 : $imageUrl;

    $fontStyleSvg = "text { font-family: 'DejaVu Sans', 'Liberation Sans', Arial, sans-serif; }";




    $svg = <<<SVG
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1080 1080" width="1080" height="1080">
        <defs>
          <style>
            {$fontStyleSvg}
          </style>

          <linearGradient id="orangeGrad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#ff4500"/>
            <stop offset="100%" stop-color="#ff6a00"/>
          </linearGradient>
          <linearGradient id="btnGrad" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#ff6a00"/>
            <stop offset="100%" stop-color="#d63a00"/>
          </linearGradient>
          <clipPath id="imgClip">
            <rect x="30" y="165" width="620" height="490" rx="16" ry="16" />
          </clipPath>
          <clipPath id="cardClip">
            <rect x="12" y="12" width="1056" height="1056" rx="24" ry="24" />
          </clipPath>
        </defs>

        <!-- 1. Background Base -->
        <rect x="0" y="0" width="1080" height="1080" fill="#0a0b0d" />
        
        <!-- 2. Thick Outer Orange Border Frame -->
        <rect x="12" y="12" width="1056" height="1056" rx="24" ry="24" fill="#0d0e12" stroke="#ff5500" stroke-width="6" />

        <!-- 3. TOP HEADER -->
        <!-- Official Eastline Brand Text (Left Side Header) -->
        <g transform="translate(170, 24)">
          <text x="0" y="54" fill="#ffffff" font-size="50" font-weight="900">EAST<tspan fill="#ff5500">LINE</tspan></text>
          <text x="2" y="86" fill="#ffffff" font-size="20" font-weight="800">EQUIPMENT AUCTIONS</text>
        </g>

        <!-- Top Right Diagonal Timer Banner (Native curved path without clip-path for Linux Imagick compatibility) -->
        <path d="M 620 12 L 1044 12 A 24 24 0 0 1 1068 36 L 1068 152 L 670 152 Z" fill="#ff5500" />

        <g transform="translate(625, 28)">
          <!-- White Circle for Clock Icon -->
          <circle cx="45" cy="44" r="40" fill="#ffffff"/>
          <!-- Clock Icon Hands -->
          <circle cx="45" cy="44" r="30" fill="none" stroke="#ff5500" stroke-width="5"/>
          <polyline points="45,26 45,44 58,44" fill="none" stroke="#ff5500" stroke-width="5" stroke-linecap="round"/>
          
          <!-- Dynamic Time Left Text -->
          <text x="100" y="44" fill="#ffffff" font-size="48" font-weight="900">{$timeLeftMainSvg}</text>
          <text x="100" y="78" fill="#ffffff" font-size="25" font-weight="800" letter-spacing="1.5">{$timeLeftSubSvg}</text>
        </g>

        <!-- 4. MAIN PRODUCT SHOWCASE & HIGHLIGHTS SECTION (2-COLUMN SPLIT) -->
        <g>
          <!-- Left Column: Machinery Photo Container (620x490 4:3 Box) -->
          <rect x="30" y="165" width="620" height="490" rx="16" ry="16" fill="#16181f" stroke="#2b2e3b" stroke-width="2" />
          <image href="{$photoSrcSvg}" x="30" y="165" width="620" height="490" preserveAspectRatio="xMidYMid slice" clip-path="url(#imgClip)" />


          <!-- Right Column: Current Bid & BID NOW Action Panel (380x490) -->
          <g transform="translate(670, 165)">
            <rect x="0" y="0" width="380" height="490" rx="16" ry="16" fill="#101217" stroke="#2b2e3b" stroke-width="2" />
            
            <!-- Orange Header Pill -->
            <rect x="25" y="20" width="330" height="44" rx="22" fill="#ff5500" />
            <text x="190" y="49" text-anchor="middle" fill="#ffffff" font-size="19" font-weight="900" letter-spacing="2">AUCTION BIDDING</text>
            
            <!-- CURRENT BID Header -->
            <text x="190" y="118" text-anchor="middle" fill="#a1a5b7" font-size="20" font-weight="800" letter-spacing="2">CURRENT BID</text>
            
            <!-- Price Text -->
            <text x="190" y="185" text-anchor="middle" fill="#ff5500" font-size="56" font-weight="900">{$formattedPriceSvg}</text>
            
            <!-- BID NOW Button -->
            <g transform="translate(45, 215)">
              <rect x="0" y="0" width="290" height="68" rx="34" fill="#ff5500" />
              
              <!-- Black Circle with White Gavel Icon -->
              <circle cx="48" cy="34" r="25" fill="#000000" />
              <g transform="translate(30, 15)">
                <!-- White Gavel (Head + Handle rotated -45 deg) -->
                <g transform="rotate(-45 18 16)">
                  <rect x="8" y="6" width="20" height="11" rx="2" fill="#ffffff" />
                  <rect x="15" y="16" width="6" height="18" rx="2" fill="#ffffff" />
                </g>
                <!-- White Base Strike Line -->
                <rect x="5" y="33" width="26" height="4" rx="2" fill="#ffffff" />
              </g>
              
              <text x="175" y="45" text-anchor="middle" fill="#ffffff" font-size="30" font-weight="900" letter-spacing="1.5">BID NOW</text>
            </g>

            <!-- Bottom Live Callout Card -->
            <g transform="translate(25, 315)">
              <rect x="0" y="0" width="330" height="145" rx="14" fill="#161922" stroke="#ff5500" stroke-width="2" />
              <text x="165" y="48" text-anchor="middle" fill="#ff5500" font-size="22" font-weight="900" letter-spacing="1">LIVE ONLINE AUCTION</text>
              <text x="165" y="88" text-anchor="middle" fill="#ffffff" font-size="18" font-weight="800">EASTLINE VERIFIED</text>
              <text x="165" y="120" text-anchor="middle" fill="#a1a5b7" font-size="14" font-weight="700">BID WITH CONFIDENCE</text>
            </g>
          </g>
        </g>

        <!-- 5. PRODUCT TITLE & CATEGORY SECTION (FULL WIDTH) -->
        <g transform="translate(30, 671)">
          <!-- Dark Main Panel -->
          <rect x="0" y="0" width="1020" height="156" rx="16" fill="#101217" stroke="#232630" stroke-width="2" />
          
          <!-- Product Title -->
          <text x="35" y="54" fill="#ffffff" font-size="42" font-weight="900" letter-spacing="0.5">{$titleSvg}</text>
          
          <!-- Divider Line -->
          <line x1="35" y1="84" x2="985" y2="84" stroke="#252833" stroke-width="2" />
          
          <!-- Lower Metadata Row (ID & CATEGORY) -->
          <g transform="translate(35, 116)">
            <!-- Tag Icon -->
            <path d="M 0 6 L 6 0 L 16 0 L 16 8 L 8 16 Z" fill="none" stroke="#a1a5b7" stroke-width="2.2"/>
            <circle cx="10" cy="5" r="1.5" fill="#a1a5b7"/>
            <text x="24" y="14" fill="#a1a5b7" font-size="19" font-weight="700">{$fullIdSvg}</text>
            
            <!-- Separator -->
            <text x="210" y="14" fill="#4a5064" font-size="19" font-weight="700">|</text>
            
            <!-- Folder Icon -->
            <g transform="translate(235, -2)">
              <path d="M 0 2 L 8 2 L 12 6 L 24 6 L 24 18 L 0 18 Z" fill="none" stroke="#ff5500" stroke-width="2.5"/>
              <text x="32" y="16" fill="#ff5500" font-size="19" font-weight="700">{$fullCategorySvg}</text>
            </g>
          </g>

        </g>

        <!-- 6. BOTTOM FEATURE BAR (TRUST BADGES) -->
        <g transform="translate(30, 843)">
          <rect x="0" y="0" width="1020" height="65" rx="10" fill="#0a0b0e" stroke="#1f222b" stroke-width="1.5"/>
          
          <!-- Badge 1: Inspected & Verified (Shield with Checkmark) -->
          <g transform="translate(30, 16)">
            <path d="M 16 2 L 30 7 L 30 18 C 30 25 22 30 16 32 C 10 30 2 25 2 18 L 2 7 Z" fill="none" stroke="#ff5500" stroke-width="3" stroke-linejoin="round"/>
            <path d="M 9 16 L 14 21 L 23 11" fill="none" stroke="#ff5500" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            <text x="42" y="15" fill="#ffffff" font-size="14" font-weight="800">INSPECTED</text>
            <text x="42" y="30" fill="#ffffff" font-size="14" font-weight="800">&amp; VERIFIED</text>
          </g>
          <line x1="245" y1="12" x2="245" y2="52" stroke="#252834" stroke-width="2"/>

          <!-- Badge 2: Quality Equipment (Sun / Gear Icon) -->
          <g transform="translate(270, 16)">
            <circle cx="16" cy="16" r="8.5" fill="none" stroke="#ff5500" stroke-width="3"/>
            <path d="M 16 1 V 5 M 16 27 V 31 M 1 16 H 5 M 27 16 H 31 M 5.4 5.4 L 8.2 8.2 M 23.8 23.8 L 26.6 26.6 M 5.4 26.6 L 8.2 23.8 M 23.8 8.2 L 26.6 5.4" stroke="#ff5500" stroke-width="3" stroke-linecap="round"/>
            <text x="42" y="15" fill="#ffffff" font-size="14" font-weight="800">QUALITY</text>
            <text x="42" y="30" fill="#ffffff" font-size="14" font-weight="800">EQUIPMENT</text>
          </g>
          <line x1="495" y1="12" x2="495" y2="52" stroke="#252834" stroke-width="2"/>

          <!-- Badge 3: Nationwide Shipping (Delivery Truck) -->
          <g transform="translate(520, 16)">
            <path d="M 1 6 H 20 V 21 H 1 Z" fill="none" stroke="#ff5500" stroke-width="3" stroke-linejoin="round"/>
            <path d="M 20 11 H 27 L 31 16 V 21 H 20 Z" fill="none" stroke="#ff5500" stroke-width="3" stroke-linejoin="round"/>
            <circle cx="7" cy="23" r="3.5" fill="#ff5500"/>
            <circle cx="26" cy="23" r="3.5" fill="#ff5500"/>
            <text x="42" y="15" fill="#ffffff" font-size="14" font-weight="800">NATIONWIDE</text>
            <text x="42" y="30" fill="#ffffff" font-size="14" font-weight="800">SHIPPING</text>
          </g>
          <line x1="755" y1="12" x2="755" y2="52" stroke="#252834" stroke-width="2"/>

          <!-- Badge 4: Secure Bidding (Lock Icon) -->
          <g transform="translate(780, 16)">
            <rect x="3" y="12" width="26" height="18" rx="2" fill="none" stroke="#ff5500" stroke-width="3"/>
            <path d="M 8 12 V 7 C 8 3.5 11 1.5 16 1.5 C 21 1.5 24 3.5 24 7 V 12" fill="none" stroke="#ff5500" stroke-width="3"/>
            <circle cx="16" cy="19" r="2.5" fill="#ff5500"/>
            <rect x="14.5" y="19" width="3" height="6" rx="1" fill="#ff5500"/>
            <text x="40" y="15" fill="#ffffff" font-size="14" font-weight="800">SECURE</text>
            <text x="40" y="30" fill="#ffffff" font-size="14" font-weight="800">BIDDING</text>
          </g>
        </g>

        <!-- 7. FOOTER BAR -->
        <g transform="translate(0, 995)">
          <!-- Centered Gradient Website Pill Container -->
          <rect x="240" y="0" width="600" height="50" rx="25" fill="#ff5500" stroke="#ff6a00" stroke-width="2" />

          <!-- Center Globe Icon + Website Link inside Pill -->
          <g transform="translate(340, 25)">
            <circle cx="15" cy="0" r="12" fill="none" stroke="#ffffff" stroke-width="2"/>
            <ellipse cx="15" cy="0" rx="5" ry="12" fill="none" stroke="#ffffff" stroke-width="1.5"/>
            <line x1="3" y1="0" x2="27" y2="0" stroke="#ffffff" stroke-width="1.5"/>
            <text x="44" y="8" fill="#ffffff" font-size="22" font-weight="900">EASTLINEAUCTIONS.COM</text>
          </g>
        </g>







      </svg>
      SVG;

    $pngBytes = $this->convertSvgToPng($svg, $machinery);

    if ($pngBytes) {
      return Response::make($pngBytes, 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.png"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
      ]);
    }

    return Response::make($svg, 200, [
      'Content-Type' => 'image/svg+xml; charset=utf-8',
      'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.svg"',
      'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ]);
  }



  private function generateCatalogPngGd($machinery, $timeLeftMain, $timeLeftSub, $formattedBidPrice, $title, $categoryName, $auctionId)
  {
    $w = 1080;
    $h = 1080;
    $img = imagecreatetruecolor($w, $h);

    $cBg = imagecolorallocate($img, 10, 11, 13);
    $cCardBg = imagecolorallocate($img, 13, 14, 18);
    $cBoxBg = imagecolorallocate($img, 16, 18, 23);
    $cOrange = imagecolorallocate($img, 255, 85, 0);
    $cWhite = imagecolorallocate($img, 255, 255, 255);
    $cMuted = imagecolorallocate($img, 161, 165, 183);
    $cBorder = imagecolorallocate($img, 35, 38, 48);

    imagefill($img, 0, 0, $cBg);

    // Outer Frame
    imagefilledrectangle($img, 12, 12, 1068, 1068, $cCardBg);
    imagerectangle($img, 12, 12, 1068, 1068, $cOrange);

    // Header Logo
    $logoLocalPath = public_path('settings/1766215409_logo.png');
    if (!file_exists($logoLocalPath)) {
      $logoLocalPath = public_path('settings/1766215409_logo.jpg');
    }
    if (file_exists($logoLocalPath)) {
      $logoImg = @imagecreatefromstring(file_get_contents($logoLocalPath));
      if ($logoImg) {
        imagecopyresampled($img, $logoImg, 32, 22, 0, 0, 125, 125, imagesx($logoImg), imagesy($logoImg));
        imagedestroy($logoImg);
      }
    }

    $fontFile = public_path('fonts/Montserrat-Bold.ttf');

    // Header Brand Text
    if (file_exists($fontFile)) {
      @imagettftext($img, 36, 0, 170, 78, $cWhite, $fontFile, "EASTLINE");
      @imagettftext($img, 15, 0, 172, 110, $cWhite, $fontFile, "EQUIPMENT AUCTIONS");
    } else {
      imagestring($img, 5, 170, 50, "EASTLINE", $cWhite);
      imagestring($img, 3, 172, 80, "EQUIPMENT AUCTIONS", $cWhite);
    }

    // Top Right Timer Banner
    imagefilledrectangle($img, 620, 12, 1068, 152, $cOrange);
    if (file_exists($fontFile)) {
      @imagettftext($img, 32, 0, 725, 72, $cWhite, $fontFile, $timeLeftMain);
      @imagettftext($img, 16, 0, 725, 106, $cWhite, $fontFile, $timeLeftSub);
    }

    // Photo Box (620x490)
    imagefilledrectangle($img, 30, 165, 650, 655, $cBoxBg);
    imagerectangle($img, 30, 165, 650, 655, $cBorder);

    // Overlay machinery photo inside 620x490 box
    if ($machinery) {
      $firstImage = \App\Models\MachineryFileManager::where('machinery_id', $machinery->id)
        ->where('type', 'image')->orderBy('id', 'asc')->first();
      if (!$firstImage && $machinery->images) {
        $firstImage = $machinery->images->first();
      }
      $imgPath = $firstImage ? $firstImage->image_path : ($machinery->main_image ?? null);
      if (!empty($imgPath)) {
        $photoUrl = FileResolverService::resolveMachineryImageUrl($imgPath);
        $photoBytes = @file_get_contents($photoUrl);
        if ($photoBytes) {
          $prodImg = @imagecreatefromstring($photoBytes);
          if ($prodImg) {
            $pw = imagesx($prodImg);
            $ph = imagesy($prodImg);
            $targetRatio = 620.0 / 490.0;
            $srcW = $pw;
            $srcH = (int) ($pw / $targetRatio);
            $srcX = 0;
            $srcY = (int) (($ph - $srcH) / 2);
            if ($srcY < 0) {
              $srcY = 0;
              $srcH = $ph;
              $srcW = (int) ($ph * $targetRatio);
              $srcX = (int) (($pw - $srcW) / 2);
            }
            imagecopyresampled($img, $prodImg, 30, 165, $srcX, $srcY, 620, 490, $srcW, $srcH);
            imagedestroy($prodImg);
          }
        }
      }
    }

    // Right Bidding Panel (380x490)
    imagefilledrectangle($img, 670, 165, 1050, 655, $cBoxBg);
    imagerectangle($img, 670, 165, 1050, 655, $cBorder);

    // Bidding Pill Header
    imagefilledrectangle($img, 695, 185, 1025, 229, $cOrange);
    if (file_exists($fontFile)) {
      @imagettftext($img, 14, 0, 755, 214, $cWhite, $fontFile, "AUCTION BIDDING");
      @imagettftext($img, 15, 0, 760, 283, $cMuted, $fontFile, "CURRENT BID");
      @imagettftext($img, 42, 0, 740, 350, $cOrange, $fontFile, $formattedBidPrice);
    }

    // BID NOW Button
    imagefilledrectangle($img, 715, 380, 1005, 448, $cOrange);
    if (file_exists($fontFile)) {
      @imagettftext($img, 22, 0, 805, 424, $cWhite, $fontFile, "BID NOW");
    }

    // Product Title Panel (1020x156)
    imagefilledrectangle($img, 30, 671, 1050, 827, $cBoxBg);
    imagerectangle($img, 30, 671, 1050, 827, $cBorder);
    if (file_exists($fontFile)) {
      @imagettftext($img, 30, 0, 65, 725, $cWhite, $fontFile, $title);
      $metaText = "ID: {$auctionId}   |   CATEGORY: {$categoryName}";
      @imagettftext($img, 15, 0, 65, 787, $cOrange, $fontFile, $metaText);
    } else {
      imagestring($img, 5, 65, 710, $title, $cWhite);
      imagestring($img, 4, 65, 770, "ID: {$auctionId} | CATEGORY: {$categoryName}", $cOrange);
    }

    // Trust Badges Bar
    imagefilledrectangle($img, 30, 843, 1050, 908, $cCardBg);
    if (file_exists($fontFile)) {
      @imagettftext($img, 11, 0, 100, 880, $cWhite, $fontFile, "INSPECTED & VERIFIED");
      @imagettftext($img, 11, 0, 340, 880, $cWhite, $fontFile, "QUALITY EQUIPMENT");
      @imagettftext($img, 11, 0, 590, 880, $cWhite, $fontFile, "NATIONWIDE SHIPPING");
      @imagettftext($img, 11, 0, 850, 880, $cWhite, $fontFile, "SECURE BIDDING");
    }

    // Footer Bar
    imagefilledrectangle($img, 240, 995, 840, 1045, $cOrange);
    if (file_exists($fontFile)) {
      @imagettftext($img, 16, 0, 350, 1028, $cWhite, $fontFile, "EASTLINEAUCTIONS.COM");
    }

    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    return $pngData;
  }


  private function convertSvgToPng($svgString, $machinery = null)
  {
    $pngBytes = null;
    // Sanitize letter-spacing attributes specifically for Linux Imagick rasterization engine to prevent text stretching
    $renderSvg = preg_replace('/letter-spacing="[^"]*"/', '', $svgString);

    // 1. Try PHP Native Imagick Extension with explicit font config
    if (extension_loaded('imagick')) {
      try {
        $im = new \Imagick();
        $fontPath = public_path('fonts/Montserrat-Bold.ttf');
        if (file_exists($fontPath)) {
          $im->setFont($fontPath);
        }
        $im->setResolution(150, 150);
        $im->readImageBlob($renderSvg);
        $im->setImageFormat('png24');
        $pngBytes = $im->getImageBlob();
        $im->clear();
        $im->destroy();
        if (!empty($pngBytes)) {
          Log::info('CatalogImage: Converted SVG to PNG using Native PHP Imagick Extension.');
        }
      } catch (\Throwable $e) {
        Log::warning('CatalogImage: Native PHP Imagick Extension failed: ' . $e->getMessage());
        $pngBytes = null;
      }
    }




    // 2. Try Intervention Image package driver
    if (empty($pngBytes) && class_exists('\Intervention\Image\ImageManagerStatic')) {
      try {
        $imgObj = \Intervention\Image\ImageManagerStatic::make($renderSvg);
        $pngBytes = (string) $imgObj->encode('png');
        if (!empty($pngBytes)) {
          Log::info('CatalogImage: Converted SVG to PNG using Intervention Image V2 (ImageManagerStatic).');
        }
      } catch (\Throwable $e) {
        Log::warning('CatalogImage: Intervention Image V2 failed: ' . $e->getMessage());
        $pngBytes = null;
      }
    }

    if (empty($pngBytes) && class_exists('\Intervention\Image\ImageManager')) {
      try {
        if (class_exists('\Intervention\Image\Drivers\Imagick\Driver')) {
          $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Imagick\Driver());
          $imgObj = $manager->read($renderSvg);
          $pngBytes = (string) $imgObj->toPng();
          if (!empty($pngBytes)) {
            Log::info('CatalogImage: Converted SVG to PNG using Intervention Image V3 (Imagick Driver).');
          }
        } elseif (class_exists('\Intervention\Image\Drivers\Gd\Driver')) {
          $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
          $imgObj = $manager->read($renderSvg);
          $pngBytes = (string) $imgObj->toPng();
          if (!empty($pngBytes)) {
            Log::info('CatalogImage: Converted SVG to PNG using Intervention Image V3 (GD Driver).');
          }
        }
      } catch (\Throwable $e) {
        Log::warning('CatalogImage: Intervention Image V3 failed: ' . $e->getMessage());
        $pngBytes = null;
      }
    }

    // 3. Fallback to CLI commands if package or Imagick extension is not enabled
    if (empty($pngBytes)) {
      $tempDir = storage_path('app/temp');
      if (!file_exists($tempDir)) {
        @mkdir($tempDir, 0777, true);
      }

      $uniq = uniqid('cat_');
      $tempSvg = $tempDir . '/' . $uniq . '.svg';
      $tempPng = $tempDir . '/' . $uniq . '.png';

      file_put_contents($tempSvg, $renderSvg);


      // Try CLI commands in sequence: convert, magick convert, rsvg-convert
      $cliCommands = [
        'convert -density 150 "' . $tempSvg . '" "' . $tempPng . '" 2>&1',
        'magick convert -density 150 "' . $tempSvg . '" "' . $tempPng . '" 2>&1',
        'rsvg-convert -w 2250 -h 2250 "' . $tempSvg . '" -o "' . $tempPng . '" 2>&1'
      ];

      foreach ($cliCommands as $cmd) {
        @exec($cmd, $output, $returnCode);
        if (file_exists($tempPng) && filesize($tempPng) > 0) {
          $pngBytes = file_get_contents($tempPng);
          Log::info('CatalogImage: Converted SVG to PNG using CLI command: ' . $cmd);
          break;
        }
      }

      @unlink($tempSvg);
      @unlink($tempPng);
    }

    if (empty($pngBytes)) {
      Log::error('CatalogImage: All SVG to PNG conversion drivers/methods failed. Returning raw SVG fallback.');
    }


    if ($pngBytes) {
      $mainImg = @imagecreatefromstring($pngBytes);
      if ($mainImg) {

        $canvasW = imagesx($mainImg);
        $scale = $canvasW / 1080.0;

        // 1. Overlay machinery photo directly onto main frame box
        if ($machinery) {
          $firstImage = \App\Models\MachineryFileManager::where('machinery_id', $machinery->id)
            ->where('type', 'image')
            ->orderBy('id', 'asc')
            ->first();
          if (!$firstImage && $machinery->images) {
            $firstImage = $machinery->images->first();
          }
          $imgPath = $firstImage ? $firstImage->image_path : ($machinery->main_image ?? null);
          if (!empty($imgPath)) {
            $photoUrl = FileResolverService::resolveMachineryImageUrl($imgPath);
            $photoBytes = null;
            if (str_starts_with($photoUrl, 'http://') || str_starts_with($photoUrl, 'https://')) {
              $photoBytes = @file_get_contents($photoUrl);
            } else {
              $cleanName = basename(parse_url($imgPath, PHP_URL_PATH) ?? $imgPath);
              $localP = public_path('uploads/machinery/images/' . $cleanName);
              if (file_exists($localP)) {
                $photoBytes = @file_get_contents($localP);
              }
            }

            if ($photoBytes) {
              $prodImg = @imagecreatefromstring($photoBytes);
              if ($prodImg) {
                $pw = imagesx($prodImg);
                $ph = imagesy($prodImg);

                $frameX = (int) (30 * $scale);
                $frameY = (int) (165 * $scale);
                $frameW = (int) (620 * $scale);
                $frameH = (int) (490 * $scale);

                // Smart Fit inside 620x490 4:3 Box (0% cropping, 0% stretching!)
                $targetRatio = $frameW / $frameH;
                $srcW = $pw;
                $srcH = (int) ($pw / $targetRatio);
                $srcX = 0;
                $srcY = (int) (($ph - $srcH) / 2);
                if ($srcY < 0) {
                  $srcY = 0;
                  $srcH = $ph;
                  $srcW = (int) ($ph * $targetRatio);
                  $srcX = (int) (($pw - $srcW) / 2);
                }


                imagecopyresampled($mainImg, $prodImg, $frameX, $frameY, $srcX, $srcY, $frameW, $frameH, $srcW, $srcH);
                imagedestroy($prodImg);



                // Apply rounded corner mask to the 4 corners of the photo frame
                $mask = imagecreatetruecolor($frameW, $frameH);
                $bg = imagecolorallocate($mask, 255, 255, 255);
                $fg = imagecolorallocate($mask, 0, 0, 0);
                imagefill($mask, 0, 0, $bg);
                $r = (int) (16 * $scale);

                imagefilledrectangle($mask, $r, 0, $frameW - $r, $frameH, $fg);
                imagefilledrectangle($mask, 0, $r, $frameW, $frameH - $r, $fg);
                imagefilledellipse($mask, $r, $r, $r * 2, $r * 2, $fg);
                imagefilledellipse($mask, $frameW - $r, $r, $r * 2, $r * 2, $fg);
                imagefilledellipse($mask, $r, $frameH - $r, $r * 2, $r * 2, $fg);
                imagefilledellipse($mask, $frameW - $r, $frameH - $r, $r * 2, $r * 2, $fg);

                $cBg = imagecolorallocate($mainImg, 13, 14, 18);
                for ($mx = 0; $mx < $frameW; $mx++) {
                  for ($my = 0; $my < $frameH; $my++) {
                    if (($mx < $r || $mx > $frameW - $r) && ($my < $r || $my > $frameH - $r)) {
                      $rgb = imagecolorat($mask, $mx, $my);
                      if ($rgb > 0x7FFFFF) {
                        imagesetpixel($mainImg, $frameX + $mx, $frameY + $my, $cBg);
                      }
                    }
                  }
                }







              }
            }
          }
        }

        // 2. Overlay official round logo directly onto top-left header
        $logoLocalPath = public_path('settings/1766215409_logo.jpg');
        if (!file_exists($logoLocalPath)) {
          $logoLocalPath = public_path('settings/1766215409_logo.png');
        }

        if (file_exists($logoLocalPath)) {
          $logoData = @file_get_contents($logoLocalPath);
          $logoImg = $logoData ? @imagecreatefromstring($logoData) : null;
          if ($logoImg) {
            $lw = imagesx($logoImg);
            $lh = imagesy($logoImg);

            $targetW = (int) (125 * $scale);
            $targetH = (int) (125 * $scale);
            $posX = (int) (32 * $scale);
            $posY = (int) (22 * $scale);

            imagecopyresampled($mainImg, $logoImg, $posX, $posY, 0, 0, $targetW, $targetH, $lw, $lh);
            imagedestroy($logoImg);
          }
        }

        ob_start();
        imagepng($mainImg);
        $pngBytes = ob_get_clean();
        imagedestroy($mainImg);
      }

      return $pngBytes;
    }

    @unlink($tempSvg);
    return null;
  }
}
