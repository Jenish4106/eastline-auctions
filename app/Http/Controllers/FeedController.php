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

    $photoSrc = $this->normalizeCatalogImageDataUri(!empty($imageBase64) ? $imageBase64 : $imageUrl);

    // 1. Try SVG to PNG Conversion (Imagick / Intervention) with embedded base64 image
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
    $liveAuctionB64  = file_exists($liveAuctionPath)
      ? 'data:image/png;base64,' . base64_encode(file_get_contents($liveAuctionPath)) : '';
    $liveAuctionE = htmlspecialchars($liveAuctionB64, ENT_XML1, 'UTF-8');

    $svg = <<<SVG
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1080 1080" width="1080" height="1080">
      <defs>
        <style>{$fontStyleSvg} .ht{font-weight:900} .sm{font-weight:800}</style>
        <linearGradient id="og" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#ea580c"/><stop offset="50%" stop-color="#f97316"/><stop offset="100%" stop-color="#ea580c"/>
        </linearGradient>
        <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#0b1320"/><stop offset="100%" stop-color="#111c2e"/>
        </linearGradient>
        <clipPath id="cp"><rect x="20" y="20" width="1040" height="1040" rx="24" ry="24"/></clipPath>
        <clipPath id="ph"><rect x="20" y="20" width="1040" height="640"/></clipPath>
      </defs>
      <rect width="1080" height="1080" fill="#f0f0f0"/>
      <rect x="20" y="20" width="1040" height="1040" rx="24" fill="#ffffff"/>
      <g clip-path="url(#cp)">
        <rect x="20" y="20" width="1040" height="640" fill="#0A1727"/>
        <image href="{$iE}" xlink:href="{$iE}" x="20" y="20" width="1040" height="640" preserveAspectRatio="xMidYMid slice" clip-path="url(#ph)"/>
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
        <path d="M668 821 Q662 821 664 829 L644 893 Q642 901 650 901 L962 901 Q970 901 972 893 L992 829 Q994 821 988 821 Z" fill="rgba(0,0,0,0.18)"/>
        <path d="M662 813 Q656 813 658 821 L637 887 Q635 895 643 895 L964 895 Q972 895 995 821 Q997 813 991 813 Z" fill="#ffffff"/>
        <path d="M664 817 Q658 817 660 825 L639 890 Q637 897 645 897 L962 897 Q970 897 972 890 L993 825 Q995 817 989 817 Z" fill="url(#og)"/>
        <circle cx="700" cy="856" r="24" fill="#ffffff"/>
        <g transform="translate(687,843) scale(1.1)"><g transform="rotate(-45 12 12)"><rect x="6" y="6" width="13" height="7" rx="1.5" fill="#ea580c"/><rect x="11" y="11" width="4" height="13" rx="1" fill="#ea580c"/></g><rect x="4" y="21" width="17" height="3" rx="1" fill="#ea580c"/></g>
        <text x="734" y="863" fill="#ffffff" font-size="26" class="ht">BID NOW</text>
        <path d="M940 844 L952 856 L940 868" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
        <rect x="20" y="960" width="1040" height="100" fill="#0A1727"/>
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
      </g>
      </svg>
      SVG;

    $svgPng = $this->convertSvgToPng($svg, $machinery);
    if ($svgPng) {
      return Response::make($svgPng, 200, [
        'Content-Type' => 'image/png',
        'Content-Disposition' => 'inline; filename="catalog-' . $auctionId . '.png"',
        'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
      ]);
    }

    // 2. GD Fallback: Pure PHP pixel-perfect renderer
    if (extension_loaded('gd')) {
      try {
        $gdPng = $this->generateCatalogPngGd(
          $machinery, $timeLeftMain, $timeLeftSub,
          $formattedBidPrice, $title, $categoryName, $auctionId
        );
        if ($gdPng) {
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
  // Canvas: 1080×1080  |  Card: 28px inset  |  matches reference design
  // ═══════════════════════════════════════════════════════════════════════════
  private function generateCatalogPngGd(
    $machinery, string $timeLeftMain, string $timeLeftSub,
    string $formattedBidPrice, string $title,
    string $categoryName, $auctionId
  ): ?string {
    // ── canvas ───────────────────────────────────────────────────────────────
    $W = 1080;
    $H = 1080;
    $img = imagecreatetruecolor($W, $H);
    imagealphablending($img, true);
    imagesavealpha($img, true);

    // palette
    $cBg = imagecolorallocate($img, 240, 240, 240);  // outer canvas
    $cWhite = imagecolorallocate($img, 255, 255, 255);
    $cDark = imagecolorallocate($img, 10, 23, 39);  // #0A1727
    $cDark2 = imagecolorallocate($img, 10, 23, 39);
    $cTitle = imagecolorallocate($img, 10, 23, 39);  // #0A1727
    $cMuted = imagecolorallocate($img, 100, 116, 139);  // #64748b
    $cOrange = imagecolorallocate($img, 249, 115, 22);  // #f97316
    $cOrangeD = imagecolorallocate($img, 234, 88, 12);  // #ea580c
    $cDivider = imagecolorallocate($img, 209, 213, 219);  // #d1d5db
    $cFootDiv = imagecolorallocate($img, 51, 65, 85);  // #334155
    $cPhBg = imagecolorallocate($img, 15, 23, 42);  // photo placeholder

    $font = file_exists(public_path('fonts/Montserrat-Bold.ttf'))
      ? public_path('fonts/Montserrat-Bold.ttf')
      : null;

    // ── outer background ─────────────────────────────────────────────────────
    imagefill($img, 0, 0, $cBg);

    // white card area (20px inset margin all sides)
    $cx = 20;
    $cy = 20;
    $cw = $W - 40;
    $ch = $H - 40;
    $this->gdFillRoundRect($img, $cx, $cy, $cw, $ch, 24, $cWhite);

    // ── hero photo area (top 60% of card) ────────────────────────────────────
    $photoX = $cx;
    $photoY = $cy;
    $photoW = $cw;
    $photoH = 640;

    // fill photo bg first
    imagefilledrectangle($img, $photoX, $photoY, $photoX + $photoW, $photoY + $photoH, $cPhBg);

    // draw machinery photo (cover crop)
    $this->drawCoverImage($img, $machinery, $photoX, $photoY, $photoW, $photoH);

    // ── diagonal white section separator ─────────────────────────────────────
    $sepTop = $photoY + $photoH;  // ~660
    $diagRise = 40;  // how many px the right side is higher
    $cardBot = $cy + $ch;  // 1060
    $whiteX = $cx;
    $whiteXr = $cx + $cw;

    imagefilledpolygon($img, [
      $whiteX,
      $sepTop,
      $whiteXr,
      $sepTop - $diagRise,
      $whiteXr,
      $cardBot,
      $whiteX,
      $cardBot,
    ], 4, $cWhite);

    // ── left + right border lines on the white section ───────────────────────
    $footerTop = $cardBot - 100;

    // ── LIVE AUCTION badge — PNG image ───────────────────────────────────────
    $liveAuctionFile = public_path('settings/live-auction.png');
    if (file_exists($liveAuctionFile)) {
      $badgeImg = @imagecreatefrompng($liveAuctionFile);
      if ($badgeImg) {
        $bw2 = imagesx($badgeImg); $bh2 = imagesy($badgeImg);
        $drawH = 78; $drawW = (int)round($bw2 * $drawH / $bh2);
        imagecopyresampled($img, $badgeImg, $cx, $cy + 38, 0, 0, $drawW, $drawH, $bw2, $bh2);
        imagedestroy($badgeImg);
      }
    }

    // ── title block ───────────────────────────────────────────────────────────
    $titleX = $cx + 56;
    $titleY = $sepTop + 100;   // enough gap below diagonal edge

    $maxTitleW = $cw - 112;
    $titleSize = $this->fitTextSize($title, 68, 34, $maxTitleW, $font);
    $this->gdText($img, $title, $titleX, $titleY, $titleSize, $cTitle, $font);

    $this->gdFillRoundRect($img, $titleX + 2, $titleY + 14, 100, 8, 4, $cOrange);

    // ── 3-column info row ─────────────────────────────────────────────────────
    $rowY = $titleY + 80;

    // Col 1 — CURRENT BID + price
    $col1X = $cx + 56;
    $this->gdText($img, 'CURRENT BID', $col1X, $rowY + 26, 20, $cMuted, $font);
    $priceSize = $this->fitTextSize($formattedBidPrice, 62, 38, 240, $font);
    $this->gdText($img, $formattedBidPrice, $col1X, $rowY + 26 + $priceSize + 8, $priceSize, $cTitle, $font);

    // vertical divider
    $div1X = $col1X + 270;
    imageline($img, $div1X, $rowY + 10, $div1X, $rowY + 108, $cDivider);

    // Col 2 — clock + time left
    $col2X = $div1X + 22;
    $clockCX = $col2X + 28; $clockCY = $rowY + 60;
    imagesetthickness($img, 5);
    imagearc($img, $clockCX, $clockCY, 54, 54, 0, 360, $cOrange);
    imageline($img, $clockCX, $clockCY - 16, $clockCX, $clockCY, $cOrange);
    imageline($img, $clockCX, $clockCY, $clockCX + 13, $clockCY, $cOrange);
    imagesetthickness($img, 1);

    $timeTextX = $col2X + 66;
    $this->gdText($img, $timeLeftMain, $timeTextX, $rowY + 48, 24, $cTitle, $font);
    $this->gdText($img, $timeLeftSub,  $timeTextX, $rowY + 78, 17, $cMuted,  $font);

    // Col 3 — BID NOW button
    $btnX  = $col2X + 66 + 170;
    $btnY  = $rowY + 4;
    $btnW  = ($cx + $cw - 30) - $btnX;
    $btnH  = 84;
    $btnR  = 16;

    for ($sh = 6; $sh >= 1; $sh--) {
      $shAlpha = 118 + (int)($sh * 1.4);
      $shC = imagecolorallocatealpha($img, 0, 0, 0, $shAlpha);
      $this->gdFillRoundRect($img, $btnX - (int)($sh / 2), $btnY + (int)($sh / 1.5), $btnW + $sh, $btnH + $sh, $btnR + 2, $shC);
    }
    $this->gdFillRoundRect($img, $btnX - 2, $btnY - 2, $btnW + 4, $btnH + 4, $btnR + 2, $cWhite);
    $this->gdFillRoundRect($img, $btnX, $btnY + (int)($btnH * 0.45), $btnW, (int)($btnH * 0.55), $btnR, $cOrangeD);
    $this->gdFillRoundRect($img, $btnX, $btnY, $btnW, $btnH, $btnR, $cOrange);

    $iconCX = $btnX + 50;
    $iconCY = $btnY + (int)($btnH / 2);
    imagefilledellipse($img, $iconCX, $iconCY, 56, 56, $cWhite);
    $this->drawGavelIcon($img, $iconCX - (int)(16 * 1.1), $iconCY - (int)(16 * 1.1), 1.1, $cOrangeD);
    $this->gdText($img, 'BID NOW', $iconCX + 36, $btnY + 59, 34, $cWhite, $font);

    $arrX = $btnX + $btnW - 34;
    $arrY = $btnY + (int)($btnH / 2);
    imagesetthickness($img, 4);
    imageline($img, $arrX, $arrY - 13, $arrX + 14, $arrY, $cWhite);
    imageline($img, $arrX + 14, $arrY, $arrX, $arrY + 13, $cWhite);
    imagesetthickness($img, 1);

    // ── footer bar with bottom rounded corners (compact 68px height) ──────────
    $footH = 68;
    $footY = $cardBot - $footH;
    $this->gdFillRoundRect($img, $cx, $footY, $cw, $footH, 24, $cDark);
    imagefilledrectangle($img, $cx, $footY, $cx + $cw, $footY + 20, $cDark);

    // Total width = 1040. 3 Equal Columns: ~346px width each.
    // Badge 1 – INSPECT ON SITE
    $f1x = $cx + 54;
    $fy = $footY + 16;
    $this->drawFooterShield($img, $f1x, $fy, $cOrange);
    $this->gdText($img, 'INSPECT ON SITE', $f1x + 48, $footY + 44, 19, $cWhite, $font);

    // divider 1
    $fd1 = $cx + 346;
    imageline($img, $fd1, $footY + 12, $fd1, $footY + 56, $cFootDiv);

    // Badge 2 – SHIPPING AVAILABLE
    $f2x = $fd1 + 30;
    $fy2 = $footY + 18;
    $this->drawFooterTruck($img, $f2x, $fy2, $cOrange);
    $this->gdText($img, 'SHIPPING AVAILABLE', $f2x + 56, $footY + 44, 19, $cWhite, $font);

    // divider 2
    $fd2 = $cx + 692;
    imageline($img, $fd2, $footY + 12, $fd2, $footY + 56, $cFootDiv);

    // Badge 3 – SECURE BIDDING
    $f3x = $fd2 + 30;
    $fy3 = $footY + 14;
    $this->drawFooterLock($img, $f3x, $fy3, $cOrange);
    $this->gdText($img, 'SECURE BIDDING', $f3x + 48, $footY + 44, 19, $cWhite, $font);

    // ── mask outer rounded corners ────────────────────────────────────────────
    $this->maskRoundedOuterCorners($img, $W, $H, $cx, 24, $cBg);

    // ── output ───────────────────────────────────────────────────────────────
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

  private function drawGavelIcon($img, $x, $y, $scale, $color)
  {
    // Clean upward-arrow / gavel icon
    $s  = (float)$scale;
    $cx = (int)($x + 16 * $s);
    $cy = (int)($y + 16 * $s);
    $r  = (int)(11 * $s);

    // vertical shaft
    imagesetthickness($img, max(2, (int)(4 * $s)));
    imageline($img, $cx, $cy - $r + 2, $cx, $cy + (int)(4 * $s), $color);
    imagesetthickness($img, 1);

    // filled arrowhead (upward triangle)
    $hw = (int)(8 * $s);
    $ht = (int)(9 * $s);
    imagefilledpolygon($img, [
      $cx,        $cy - $r,
      $cx - $hw,  $cy - $r + $ht,
      $cx + $hw,  $cy - $r + $ht,
    ], 3, $color);

    // base bar
    $bw2 = (int)(9 * $s);
    $bh2 = max(2, (int)(3 * $s));
    imagefilledrectangle($img,
      $cx - $bw2, $cy + (int)(5 * $s),
      $cx + $bw2, $cy + (int)(5 * $s) + $bh2,
      $color
    );
    imagesetthickness($img, 1);
  }

  private function drawFooterShield($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagepolygon($img, [$x + 20, $y, $x + 39, $y + 8, $x + 39, $y + 28, $x + 20, $y + 47, $x + 1, $y + 28, $x + 1, $y + 8], 6, $color);
    imageline($img, $x + 11, $y + 23, $x + 18, $y + 31, $color);
    imageline($img, $x + 18, $y + 31, $x + 31, $y + 15, $color);
    imagesetthickness($img, 1);
  }

  private function drawFooterTruck($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagerectangle($img, $x, $y + 8, $x + 28, $y + 32, $color);
    imagepolygon($img, [$x + 28, $y + 17, $x + 43, $y + 17, $x + 53, $y + 27, $x + 53, $y + 32, $x + 28, $y + 32], 5, $color);
    imageellipse($img, $x + 12, $y + 37, 11, 11, $color);
    imageellipse($img, $x + 43, $y + 37, 11, 11, $color);
    imagesetthickness($img, 1);
  }

  private function drawFooterLock($img, $x, $y, $color)
  {
    imagesetthickness($img, 3);
    imagerectangle($img, $x, $y + 16, $x + 28, $y + 36, $color);
    imagearc($img, $x + 14, $y + 16, 20, 24, 180, 360, $color);
    imageline($img, $x + 4, $y + 16, $x + 4, $y + 9, $color);
    imageline($img, $x + 24, $y + 16, $x + 24, $y + 9, $color);
    imagefilledellipse($img, $x + 14, $y + 26, 4, 4, $color);
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
