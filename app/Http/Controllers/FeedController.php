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
        $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
        $currentBidVal = $highestBid ?? ($machinery->buy_now_price > 0 ? $machinery->buy_now_price : $machinery->bid_start_price);
        $formattedBidPrice = '$' . number_format((float) $currentBidVal, 0);

        $startTime = !empty($machinery->bid_start_time) ? Carbon::parse($machinery->bid_start_time) : null;
        $endTime = !empty($machinery->bid_end_time) ? Carbon::parse($machinery->bid_end_time) : null;

        $hasStarted = !$startTime || $startTime->lte($now);
        $isSoldOrCompleted = in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true) || !empty($machinery->won_user);
        $isActive = ($machinery->status == 1) && $hasStarted && !$isSoldOrCompleted && ($endTime && $endTime->gt($now));

        $availability = $isActive ? 'in stock' : 'out of stock';

        $year = $machinery->year ?? '';
        $make = $machinery->make ?? '';
        $model = $machinery->model ?? '';
        $title = trim("$year $make $model");
        if (empty($title)) {
          $title = 'Machinery #' . ($machinery->auction_id ?? $machinery->id);
        }

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

        $price = number_format((float) $currentBidVal, 2, '.', '') . ' USD';

        $brand = !empty($machinery->make) ? $machinery->make : 'Eastline Auctions';

        $categoryName = $machinery->category ? $machinery->category->category_name : '';
        $productType = ($categoryName && !empty($machinery->make)) ? "{$categoryName} > {$machinery->make}" : $categoryName;

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
    $cleanId = preg_replace('/\.(png|jpg|jpeg|svg)$/i', '', $id);

    $machinery = Machinery::with(['category', 'bids', 'images' => function ($q) {
      $q->where('type', 'image')->orderBy('id');
    }])
      ->where('auction_id', $cleanId)
      ->orWhere('id', $cleanId)
      ->first();

    if (!$machinery) {
      $png = $this->makeNotFoundPng();
      return Response::make($png, 404, ['Content-Type' => 'image/png']);
    }

    $now = Carbon::now();

    $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
    $currentBidVal = $highestBid ?? ($machinery->bid_start_price > 0 ? $machinery->bid_start_price : $machinery->buy_now_price);
    $formattedBidPrice = '$' . number_format((float) $currentBidVal, 0);

    $startTime = !empty($machinery->bid_start_time) ? Carbon::parse($machinery->bid_start_time) : null;
    $endTime = !empty($machinery->bid_end_time) ? Carbon::parse($machinery->bid_end_time) : null;
    $isSoldOrCompleted = in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true) || !empty($machinery->won_user);

    $timeLeftMain = '';
    $timeLeftSub = '';

    if ($startTime && $startTime->gt($now)) {
      $timeLeftMain = 'SOON';
      $timeLeftSub = 'STARTS SOON';
    } elseif ($endTime && $endTime->gt($now) && !$isSoldOrCompleted) {
      $diff = $now->diffInSeconds($endTime, false);
      $days = (int) floor($diff / 86400);
      $hours = (int) floor(($diff % 86400) / 3600);
      $minutes = (int) floor(($diff % 3600) / 60);
      if ($days > 0) {
        $timeLeftMain = $days === 1 ? '1 DAY' : "{$days} DAYS";
        $timeLeftSub = 'LEFT TO BID';
      } elseif ($hours > 0) {
        $timeLeftMain = $hours === 1 ? '1 HOUR' : "{$hours} HOURS";
        $timeLeftSub = 'LEFT TO BID';
      } else {
        $m = max(1, $minutes);
        $timeLeftMain = $m === 1 ? '1 MIN' : "{$m} MINS";
        $timeLeftSub = 'LEFT TO BID';
      }
    } elseif ($endTime && $endTime->lte($now)) {
      $timeLeftMain = 'ENDED';
      $timeLeftSub = 'AUCTION ENDED';
    } else {
      $timeLeftMain = 'LIVE';
      $timeLeftSub = 'AUCTION OPEN';
    }

    $categoryName = strtoupper($machinery->category ? $machinery->category->category_name : 'EQUIPMENT');
    $year = $machinery->year ?? '';
    $make = $machinery->make ?? '';
    $model = $machinery->model ?? '';
    $title = strtoupper(trim("$year $make $model"));
    if (empty($title)) {
      $title = 'MACHINERY #' . ($machinery->auction_id ?? $machinery->id);
    }
    $auctionId = $machinery->auction_id ?? $machinery->id;

    $imagePath = null;
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

    if ((empty($imageBase64) || str_contains($imageBase64, 'defaults/default.png')) && !empty($imagePath)) {
      $cleanName = basename(parse_url($imagePath, PHP_URL_PATH) ?? $imagePath);
      $possibleLocalPaths = [
        public_path('uploads/machinery/images/' . $cleanName),
        public_path('public/uploads/machinery/images/' . $cleanName),
        storage_path('app/public/uploads/machinery/images/' . $cleanName),
      ];
      foreach ($possibleLocalPaths as $localP) {
        if (file_exists($localP)) {
          $mime = mime_content_type($localP) ?: 'image/jpeg';
          $imageBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localP));
          break;
        }
      }
    }
    if ((empty($imageBase64) || str_contains($imageBase64, 'defaults/default.png')) && !empty($imageUrl)) {
      $imageBase64 = $this->fetchCatalogImageDataUri($imageUrl) ?: $imageBase64;
    }

    $photoSrc = $this->getCropCatalogPhotoDataUri($imagePath, 1040, 640);

    $fontPath = public_path('fonts/Montserrat-Bold.ttf');
    $fontStyleSvg = file_exists($fontPath)
      ? "@font-face{font-family:'EC';src:url('data:font/truetype;base64," . base64_encode(file_get_contents($fontPath)) . "') format('truetype');font-weight:900} text{font-family:'EC','Montserrat',Arial,sans-serif}"
      : "text{font-family:'Montserrat','DejaVu Sans',Arial,sans-serif}";

    $tE = htmlspecialchars($title, ENT_XML1, 'UTF-8');
    $pE = htmlspecialchars($formattedBidPrice, ENT_XML1, 'UTF-8');
    $mE = htmlspecialchars($timeLeftMain, ENT_XML1, 'UTF-8');
    $sE = htmlspecialchars($timeLeftSub, ENT_XML1, 'UTF-8');
    $iE = htmlspecialchars($photoSrc, ENT_XML1, 'UTF-8');

    $liveAuctionPath = public_path('settings/live-auction.png');
    $liveAuctionB64 = file_exists($liveAuctionPath)
      ? 'data:image/png;base64,' . base64_encode(file_get_contents($liveAuctionPath))
      : '';
    $liveAuctionE = htmlspecialchars($liveAuctionB64, ENT_XML1, 'UTF-8');

    $bidNowPath = public_path('settings/bid-now.png');
    $bidNowB64 = file_exists($bidNowPath)
      ? 'data:image/png;base64,' . base64_encode(file_get_contents($bidNowPath))
      : '';
    $bidNowE = htmlspecialchars($bidNowB64, ENT_XML1, 'UTF-8');

    $svg = <<<SVG
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1080 1080" width="1080" height="1080">
      <defs>
        <style>{$fontStyleSvg} .ht{font-weight:900} .sm{font-weight:800}</style>
      </defs>
      <rect width="1080" height="1080" fill="#f0f0f0"/>
      <rect x="20" y="20" width="1040" height="1040" rx="24" fill="#ffffff"/>
      <rect x="20" y="20" width="1040" height="640" fill="#ffffff"/>
      <image href="{$iE}" xlink:href="{$iE}" x="20" y="20" width="1040" height="640" preserveAspectRatio="xMidYMid slice"/>
      <path d="M 20 20 L 44 20 A 24 24 0 0 0 20 44 Z" fill="#f0f0f0"/>
      <path d="M 1060 20 L 1036 20 A 24 24 0 0 1 1060 44 Z" fill="#f0f0f0"/>
      <path d="M20 660 L840 620 L1060 660 L1060 960 L20 960Z" fill="#ffffff"/>
      <image href="{$liveAuctionE}" xlink:href="{$liveAuctionE}" x="20" y="44" width="320" height="80" preserveAspectRatio="xMinYMid meet"/>
      <text x="56" y="752" fill="#0A1727" font-size="66" class="ht">{$tE}</text>
      <rect x="58" y="766" width="100" height="7" rx="3.5" fill="#f97316"/>
      <text x="56" y="808" fill="#6b7280" font-size="20" class="sm">CURRENT BID</text>
      <text x="56" y="884" fill="#0A1727" font-size="64" class="ht">{$pE}</text>
      <line x1="348" y1="802" x2="348" y2="912" stroke="#d1d5db" stroke-width="2"/>
      <circle cx="396" cy="856" r="26" fill="none" stroke="#f97316" stroke-width="4"/>
      <polyline points="396,842 396,856 408,856" fill="none" stroke="#f97316" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      <text x="430" y="848" fill="#0A1727" font-size="24" class="ht">{$mE}</text>
      <text x="430" y="872" fill="#6b7280" font-size="16" class="sm">{$sE}</text>
      <image href="{$bidNowE}" xlink:href="{$bidNowE}" x="785" y="810" width="255" height="91" preserveAspectRatio="none"/>
      <path d="M20 960 L1060 960 L1060 1036 Q1060 1060 1036 1060 L44 1060 Q20 1060 20 1036 Z" fill="#0A1727"/>
      <g transform="translate(68, 993)">
        <path d="M16 0 L32 6 L32 20 C32 28 23 34 16 36 C9 34 0 28 0 20 L0 6Z" fill="none" stroke="#f97316" stroke-width="2.5"/>
        <path d="M8 18 L14 24 L26 11" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
        <text x="42" y="26" fill="#ffffff" font-size="20" class="sm">INSPECT ON SITE</text>
      </g>
      <line x1="370" y1="978" x2="370" y2="1052" stroke="#334155" stroke-width="2"/>
      <g transform="translate(400, 999)">
        <rect x="0" y="0" width="26" height="22" rx="1" fill="none" stroke="#f97316" stroke-width="2.5"/>
        <path d="M26 7 H36 L44 14 V22 H26Z" fill="none" stroke="#f97316" stroke-width="2.5"/>
        <circle cx="8" cy="25" r="4" fill="#f97316"/>
        <circle cx="36" cy="25" r="4" fill="#f97316"/>
        <text x="56" y="19" fill="#ffffff" font-size="20" class="sm">SHIPPING AVAILABLE</text>
      </g>
      <line x1="718" y1="978" x2="718" y2="1052" stroke="#334155" stroke-width="2"/>
      <g transform="translate(748, 998)">
        <rect x="0" y="13" width="26" height="20" rx="2" fill="none" stroke="#f97316" stroke-width="2.5"/>
        <path d="M4 13 V8 C4 3 7 0 13 0 C19 0 22 3 22 8 V13" fill="none" stroke="#f97316" stroke-width="2.5"/>
        <circle cx="13" cy="22" r="2.5" fill="#f97316"/>
        <text x="36" y="20" fill="#ffffff" font-size="20" class="sm">SECURE BIDDING</text>
      </g>
      </svg>
      SVG;

    // 1. Primary: Try SVG to PNG conversion (Imagick) with clean SVG
    $svgPng = $this->convertSvgToPng($svg, $machinery);
    if (!empty($svgPng)) {
      return Response::make($svgPng, 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.png"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
      ]);
    }

    // 2. Fallback: Pure GD Renderer
    if (extension_loaded('gd')) {
      try {
        $gdPng = $this->generateCatalogPngGd(
          $machinery, $timeLeftMain, $timeLeftSub,
          $formattedBidPrice, $title, $categoryName, $auctionId
        );
        if (!empty($gdPng)) {
          return Response::make($gdPng, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.png"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
          ]);
        }
      } catch (\Throwable $e) {
        Log::warning('CatalogImage GD fallback failed: ' . $e->getMessage());
      }
    }

    return Response::make($svg, 200, [
      'Content-Type' => 'image/svg+xml; charset=utf-8',
      'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.svg"',
      'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ]);
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // GD RENDERER  (primary – pure PHP, no external dependencies)
  private function generateCatalogPngGd(
    $machinery, string $timeLeftMain, string $timeLeftSub,
    string $formattedBidPrice, string $title,
    string $categoryName, $auctionId
  ): ?string {
    $W = 1080;
    $H = 1080;
    $img = imagecreatetruecolor($W, $H);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    $cBg = imagecolorallocate($img, 240, 240, 240);
    $cWhite = imagecolorallocate($img, 255, 255, 255);
    $cDark = imagecolorallocate($img, 10, 23, 39);  // #0A1727
    $cTitle = imagecolorallocate($img, 10, 23, 39);  // #0A1727
    $cMuted = imagecolorallocate($img, 107, 114, 128);  // #6b7280
    $cOrange = imagecolorallocate($img, 249, 115, 22);  // #f97316
    $cOrangeD = imagecolorallocate($img, 234, 88, 12);  // #ea580c
    $cDivider = imagecolorallocate($img, 209, 213, 219);  // #d1d5db
    $cFootDiv = imagecolorallocate($img, 51, 65, 85);  // #334155
    $cPhBg = imagecolorallocate($img, 10, 23, 39);  // #0A1727

    $font = file_exists(public_path('fonts/Montserrat-Bold.ttf'))
      ? public_path('fonts/Montserrat-Bold.ttf')
      : null;

    // Outer background
    imagefill($img, 0, 0, $cBg);

    // White card area (20px inset)
    $cx = 20;
    $cy = 20;
    $cw = 1040;
    $ch = 1040;
    $this->gdFillRoundRect($img, $cx, $cy, $cw, $ch, 24, $cWhite);

    // Hero photo area
    $photoX = 20;
    $photoY = 20;
    $photoW = 1040;
    $photoH = 640;
    imagefilledrectangle($img, $photoX, $photoY, $photoX + $photoW, $photoY + $photoH, $cPhBg);
    $this->drawCoverImage($img, $machinery, $photoX, $photoY, $photoW, $photoH);

    // V-shaped diagonal white section (peak at x=840, y=620)
    imagefilledpolygon($img, [
      20,
      660,
      840,
      620,
      1060,
      660,
      1060,
      960,
      20,
      960,
    ], 5, $cWhite);

    // LIVE AUCTION badge
    $liveAuctionFile = public_path('settings/live-auction.png');
    if (file_exists($liveAuctionFile)) {
      $badgeImg = @imagecreatefrompng($liveAuctionFile);
      if ($badgeImg) {
        $bw2 = imagesx($badgeImg);
        $bh2 = imagesy($badgeImg);
        $drawH = 80;
        $drawW = (int) round($bw2 * $drawH / $bh2);
        imagecopyresampled($img, $badgeImg, 20, 44, 0, 0, $drawW, $drawH, $bw2, $bh2);
        imagedestroy($badgeImg);
      }
    }

    // Title
    $titleX = 56;
    $titleY = 752;
    $maxTitleW = 960;
    $titleSize = $this->fitTextSize($title, 66, 32, $maxTitleW, $font);
    $this->gdText($img, $title, $titleX, $titleY, $titleSize, $cTitle, $font);

    // Orange accent bar under title
    $this->gdFillRoundRect($img, 58, 766, 100, 7, 3, $cOrange);

    // CURRENT BID label
    $this->gdText($img, 'CURRENT BID', 56, 808, 20, $cMuted, $font);

    // Price text
    $priceSize = $this->fitTextSize($formattedBidPrice, 64, 38, 270, $font);
    $this->gdText($img, $formattedBidPrice, 56, 884, $priceSize, $cTitle, $font);

    // Vertical divider
    imagesetthickness($img, 2);
    imageline($img, 348, 802, 348, 912, $cDivider);

    // Clock icon
    imagesetthickness($img, 4);
    imagearc($img, 396, 856, 52, 52, 0, 360, $cOrange);
    imagesetthickness($img, 3);
    imageline($img, 396, 842, 396, 856, $cOrange);
    imageline($img, 396, 856, 408, 856, $cOrange);
    imagesetthickness($img, 1);

    // Time text
    $this->gdText($img, $timeLeftMain, 430, 848, 24, $cTitle, $font);
    $this->gdText($img, $timeLeftSub, 430, 872, 16, $cMuted, $font);

    // BID NOW PNG image
    $bidNowFile = public_path('settings/bid-now.png');
    if (file_exists($bidNowFile)) {
      $btnImg = @imagecreatefrompng($bidNowFile);
      if ($btnImg) {
        $bw = imagesx($btnImg);
        $bh = imagesy($btnImg);
        $drawH = 91;
        $drawW = (int) round($bw * $drawH / $bh);
        imagecopyresampled($img, $btnImg, 1040 - $drawW - 20, 810, 0, 0, $drawW, $drawH, $bw, $bh);
        imagedestroy($btnImg);
      }
    }

    // Footer bar (100px height, y: 960 to 1060)
    $this->gdFillRoundRect($img, 20, 960, 1040, 100, 24, $cDark);
    imagefilledrectangle($img, 20, 960, 1060, 980, $cDark);

    // Badge 1: INSPECT ON SITE
    $this->drawFooterShield($img, 68, 993, $cOrange);
    $this->gdText($img, 'INSPECT ON SITE', 110, 1019, 20, $cWhite, $font);

    // Divider 1
    imagesetthickness($img, 2);
    imageline($img, 370, 978, 370, 1052, $cFootDiv);

    // Badge 2: SHIPPING AVAILABLE
    $this->drawFooterTruck($img, 400, 999, $cOrange);
    $this->gdText($img, 'SHIPPING AVAILABLE', 456, 1018, 20, $cWhite, $font);

    // Divider 2
    imageline($img, 718, 978, 718, 1052, $cFootDiv);

    // Badge 3: SECURE BIDDING
    $this->drawFooterLock($img, 748, 998, $cOrange);
    $this->gdText($img, 'SECURE BIDDING', 784, 1018, 20, $cWhite, $font);
    imagesetthickness($img, 1);

    // Mask outer rounded corners
    $this->maskRoundedOuterCorners($img, $W, $H, 20, 24, $cBg);

    ob_start();
    imagepng($img);
    $bytes = ob_get_clean();
    imagedestroy($img);
    return $bytes ?: null;
  }

  // ── helper: not-found 1×1 transparent PNG fallback ───────────────────────
  private function makeNotFoundPng(): string
  {
    $img = imagecreatetruecolor(1080, 1080);
    imagefill($img, 0, 0, imagecolorallocate($img, 11, 12, 14));
    $font = file_exists(public_path('fonts/Montserrat-Bold.ttf'))
      ? public_path('fonts/Montserrat-Bold.ttf')
      : null;
    $c = imagecolorallocate($img, 255, 255, 255);
    $this->gdText($img, 'AUCTION ITEM NOT FOUND', 160, 560, 36, $c, $font);
    ob_start();
    imagepng($img);
    $b = ob_get_clean();
    imagedestroy($img);
    return $b;
  }

  // ── helper: filled rounded rectangle ─────────────────────────────────────
  private function gdFillRoundRect($img, int $x, int $y, int $w, int $h, int $r, $color): void
  {
    imagefilledrectangle($img, $x + $r, $y, $x + $w - $r, $y + $h, $color);
    imagefilledrectangle($img, $x, $y + $r, $x + $w, $y + $h - $r, $color);
    imagefilledellipse($img, $x + $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $w - $r, $y + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $r, $y + $h - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x + $w - $r, $y + $h - $r, $r * 2, $r * 2, $color);
  }

  // ── helper: paint bg colour over the 4 outer corners to simulate border-radius
  private function maskRoundedOuterCorners($img, int $W, int $H, int $margin, int $radius, $bgColor): void
  {
    for ($yy = 0; $yy < $margin + $radius; $yy++) {
      for ($xx = 0; $xx < $margin + $radius; $xx++) {
        if ($xx < $margin ||
            $yy < $margin ||
            hypot($xx - ($margin + $radius), $yy - ($margin + $radius)) > $radius) {
          imagesetpixel($img, $xx, $yy, $bgColor);
          imagesetpixel($img, $W - 1 - $xx, $yy, $bgColor);
          imagesetpixel($img, $xx, $H - 1 - $yy, $bgColor);
          imagesetpixel($img, $W - 1 - $xx, $H - 1 - $yy, $bgColor);
        }
      }
    }
  }

  // ── helper: imagettftext wrapper ──────────────────────────────────────────
  private function gdText($img, string $text, int $x, int $y, int $size, $color, ?string $font): void
  {
    if ($font && function_exists('imagettftext')) {
      @imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
    } else {
      imagestring($img, 5, $x, $y - $size, $text, $color);
    }
  }

  // ── helper: shrink font size until text fits maxWidth ─────────────────────
  private function fitTextSize(string $text, int $size, int $minSize, int $maxWidth, ?string $font): int
  {
    if (!$font || !function_exists('imagettfbbox')) {
      return $size;
    }
    while ($size > $minSize) {
      $box = @imagettfbbox($size, 0, $font, $text);
      $width = $box ? abs($box[2] - $box[0]) : 0;
      if ($width <= $maxWidth)
        break;
      $size -= 2;
    }
    return $size;
  }

  private function drawCoverImage($canvas, $machinery, $x, $y, $targetW, $targetH)
  {
    $photoBytes = null;

    if ($machinery) {
      $firstImage = \App\Models\MachineryFileManager::where('machinery_id', $machinery->id)
        ->where('type', 'image')
        ->orderBy('id', 'asc')
        ->first();
      if (!$firstImage && $machinery->images) {
        $firstImage = $machinery->images->first();
      }

      $imgPath = $firstImage ? $firstImage->image_path : ($machinery->main_image ?? null);
      $photoBytes = $this->readMachineryImageBytes($imgPath);
    }

    $photo = $photoBytes ? @imagecreatefromstring($photoBytes) : null;
    if (!$photo) {
      imagefilledrectangle($canvas, $x, $y, $x + $targetW, $y + $targetH, imagecolorallocate($canvas, 61, 74, 88));
      return;
    }

    $pw = imagesx($photo);
    $ph = imagesy($photo);
    $targetRatio = $targetW / $targetH;
    $sourceRatio = $pw / $ph;

    if ($sourceRatio > $targetRatio) {
      $srcH = $ph;
      $srcW = (int) round($ph * $targetRatio);
      $srcX = (int) round(($pw - $srcW) / 2);
      $srcY = 0;
    } else {
      $srcW = $pw;
      $srcH = (int) round($pw / $targetRatio);
      $srcX = 0;
      $srcY = (int) round(($ph - $srcH) / 2);
    }

    imagecopyresampled($canvas, $photo, $x, $y, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);
    imagedestroy($photo);
  }

  private function readMachineryImageBytes($imagePath)
  {
    if (empty($imagePath)) {
      return null;
    }

    $cleanName = basename(parse_url($imagePath, PHP_URL_PATH) ?? $imagePath);
    $possibleLocalPaths = [
      public_path('uploads/machinery/images/' . $cleanName),
      public_path('public/uploads/machinery/images/' . $cleanName),
      storage_path('app/public/uploads/machinery/images/' . $cleanName),
      storage_path('app/uploads/machinery/images/' . $cleanName),
      base_path('uploads/machinery/images/' . $cleanName),
    ];

    foreach ($possibleLocalPaths as $localPath) {
      if (file_exists($localPath) && is_file($localPath)) {
        $content = @file_get_contents($localPath);
        if (!empty($content)) {
          return $content;
        }
      }
    }

    $photoUrl = FileResolverService::resolveMachineryImageUrl($imagePath);
    if (!empty($photoUrl) && (str_starts_with($photoUrl, 'http://') || str_starts_with($photoUrl, 'https://'))) {
      if (!str_contains($photoUrl, 'defaults/default.png')) {
        return $this->fetchRawUrlBytes($photoUrl);
      }
    }

    return null;
  }

  private function fetchRawUrlBytes(?string $url): ?string
  {
    if (empty($url) || (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://'))) {
      return null;
    }

    $cleanUrl = strtok($url, '?');
    $content = null;

    if (function_exists('curl_init')) {
      $ch = curl_init($cleanUrl);
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) EastlineCatalog/1.0',
      ]);
      $response = curl_exec($ch);
      $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($response !== false && $status >= 200 && $status < 300) {
        $content = $response;
      }
    }

    if (empty($content)) {
      $context = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'EastlineCatalog/1.0'],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
      ]);
      $content = @file_get_contents($cleanUrl, false, $context) ?: null;
    }

    return $content;
  }

  private function getCropCatalogPhotoDataUri(?string $imagePath, int $targetW = 1040, int $targetH = 640): string
  {
    $photoBytes = $this->readMachineryImageBytes($imagePath);
    if (!$photoBytes) {
      $fallbackUrl = FileResolverService::resolveMachineryImageUrl($imagePath);
      return $this->fetchCatalogImageDataUri($fallbackUrl) ?: $fallbackUrl;
    }

    $srcImg = @imagecreatefromstring($photoBytes);
    if (!$srcImg) {
      $fallbackUrl = FileResolverService::resolveMachineryImageUrl($imagePath);
      return $this->fetchCatalogImageDataUri($fallbackUrl) ?: $fallbackUrl;
    }

    $inset = 3;
    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);
    $pw = max(1, $origW - ($inset * 2));
    $ph = max(1, $origH - ($inset * 2));
    $srcXOff = ($origW > $inset * 2) ? $inset : 0;
    $srcYOff = ($origH > $inset * 2) ? $inset : 0;

    $targetRatio = $targetW / $targetH;
    $sourceRatio = $pw / $ph;

    if ($sourceRatio > $targetRatio) {
      $srcH = $ph;
      $srcW = (int) round($ph * $targetRatio);
      $srcX = $srcXOff + (int) round(($pw - $srcW) / 2);
      $srcY = $srcYOff;
    } else {
      $srcW = $pw;
      $srcH = (int) round($pw / $targetRatio);
      $srcX = $srcXOff;
      $srcY = $srcYOff + (int) round(($ph - $srcH) / 2);
    }

    $dstImg = imagecreatetruecolor($targetW, $targetH);
    imagealphablending($dstImg, false);
    imagesavealpha($dstImg, true);

    imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);
    imagedestroy($srcImg);

    // Apply transparent 24px top-left and top-right corner mask
    $radius = 24;
    $cTrans = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
    for ($y = 0; $y < $radius; $y++) {
      for ($x = 0; $x < $radius; $x++) {
        $dx = $radius - $x;
        $dy = $radius - $y;
        if (($dx * $dx + $dy * $dy) > ($radius * $radius)) {
          imagesetpixel($dstImg, $x, $y, $cTrans);
          imagesetpixel($dstImg, $targetW - 1 - $x, $y, $cTrans);
        }
      }
    }

    ob_start();
    imagepng($dstImg);
    $croppedBytes = ob_get_clean();
    imagedestroy($dstImg);

    return 'data:image/png;base64,' . base64_encode($croppedBytes);
  }

  private function drawGavelIcon($img, $x, $y, $scale, $color)
  {
    $s = (float) $scale;
    $cx = (int) ($x + 16 * $s);
    $cy = (int) ($y + 16 * $s);
    $r = (int) (11 * $s);

    imagesetthickness($img, max(2, (int) (4 * $s)));
    imageline($img, $cx, $cy - $r + 2, $cx, $cy + (int) (4 * $s), $color);
    imagesetthickness($img, 1);

    $hw = (int) (8 * $s);
    $ht = (int) (9 * $s);
    imagefilledpolygon($img, [
      $cx,
      $cy - $r,
      $cx - $hw,
      $cy - $r + $ht,
      $cx + $hw,
      $cy - $r + $ht,
    ], 3, $color);

    $bw2 = (int) (9 * $s);
    $bh2 = max(2, (int) (3 * $s));
    imagefilledrectangle($img,
      $cx - $bw2, $cy + (int) (5 * $s),
      $cx + $bw2, $cy + (int) (5 * $s) + $bh2,
      $color);
    imagesetthickness($img, 1);
  }

  private function drawFooterShield($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagepolygon($img, [
      $x + 16,
      $y,
      $x + 32,
      $y + 6,
      $x + 32,
      $y + 20,
      $x + 16,
      $y + 36,
      $x + 0,
      $y + 20,
      $x + 0,
      $y + 6,
    ], 6, $color);
    imageline($img, $x + 8, $y + 18, $x + 14, $y + 24, $color);
    imageline($img, $x + 14, $y + 24, $x + 26, $y + 11, $color);
    imagesetthickness($img, 1);
  }

  private function drawFooterTruck($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagerectangle($img, $x, $y, $x + 26, $y + 22, $color);
    imagepolygon($img, [$x + 26, $y + 7, $x + 36, $y + 7, $x + 44, $y + 14, $x + 44, $y + 22, $x + 26, $y + 22], 5, $color);
    imageellipse($img, $x + 8, $y + 25, 8, 8, $color);
    imageellipse($img, $x + 36, $y + 25, 8, 8, $color);
    imagesetthickness($img, 1);
  }

  private function drawFooterLock($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagerectangle($img, $x, $y + 13, $x + 26, $y + 33, $color);
    imagearc($img, $x + 13, $y + 13, 18, 22, 180, 360, $color);
    imageline($img, $x + 4, $y + 13, $x + 4, $y + 8, $color);
    imageline($img, $x + 22, $y + 13, $x + 22, $y + 8, $color);
    imagefilledellipse($img, $x + 13, $y + 22, 5, 5, $color);
    imagesetthickness($img, 1);
  }

  private function fetchCatalogImageDataUri(?string $imageUrl): ?string
  {
    $content = $this->fetchRawUrlBytes($imageUrl);
    if (empty($content)) {
      return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_buffer($finfo, $content) ?: 'image/jpeg';
    finfo_close($finfo);

    return 'data:' . $mime . ';base64,' . base64_encode($content);
  }

  private function normalizeCatalogImageDataUri(string $imageSrc): string
  {
    if (!str_starts_with($imageSrc, 'data:image/')) {
      return $imageSrc;
    }

    if (preg_match('/^data:image\/[^;]+;base64,([^,]+)/', $imageSrc, $matches) !== 1) {
      return $imageSrc;
    }

    $head = substr($matches[1], 0, 16);
    $mime = null;

    if (str_starts_with($head, '/9j')) {
      $mime = 'image/jpeg';
    } elseif (str_starts_with($head, 'iVBOR')) {
      $mime = 'image/png';
    } elseif (str_starts_with($head, 'R0lGOD')) {
      $mime = 'image/gif';
    } elseif (str_starts_with($head, 'UklGR')) {
      $mime = 'image/webp';
    }

    return $mime ? preg_replace('/^data:image\/[^;]+;base64,/', 'data:' . $mime . ';base64,', $imageSrc) : $imageSrc;
  }

  private function convertSvgToPng($svgString, $machinery = null)
  {
    $pngBytes = null;
    $renderSvg = preg_replace('/letter-spacing="[^"]*"/', '', $svgString);
    $renderSvg = preg_replace('/@font-face\s*\{[^}]*\}/s', '', $renderSvg);

    if (extension_loaded('imagick')) {
      try {
        $im = new \Imagick();
        $im->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 512 * 1024 * 1024);
        $im->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512 * 1024 * 1024);
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

    if (empty($pngBytes)) {
      $tempDir = storage_path('app/temp');
      if (!file_exists($tempDir)) {
        @mkdir($tempDir, 0777, true);
      }

      $uniq = uniqid('cat_');
      $tempSvg = $tempDir . '/' . $uniq . '.svg';
      $tempPng = $tempDir . '/' . $uniq . '.png';

      file_put_contents($tempSvg, $renderSvg);

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

    return $pngBytes;
  }
}
