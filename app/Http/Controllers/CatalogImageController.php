<?php

namespace App\Http\Controllers;

use App\Models\Machinery;
use App\Services\FileResolverService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CatalogImageController extends Controller
{
    public function generateImage($id)
    {
        $machinery = Machinery::with(['images' => function ($q) {
            $q->where('type', 'image')->orderBy('id');
        }])->find($id);

        if (!$machinery) {
            abort(404, 'Machinery not found');
        }

        // 1. Calculate dynamic values
        $year = $machinery->year ?? '';
        $make = $machinery->make ?? '';
        $model = $machinery->model ?? '';
        $productTitle = trim("$year $make $model");
        if (empty($productTitle)) {
            $productTitle = "Machinery #{$machinery->id}";
        }

        // Current Bid
        $highestBid = $machinery->bids()->where('auction_id', $machinery->auction_id)->max('amount');
        $currentBidVal = $highestBid ?? ($machinery->buy_now_price > 0 ? $machinery->buy_now_price : $machinery->bid_start_price);
        $currentBidText = '$' . number_format((float) $currentBidVal, 2);

        // 2. Fetch primary product image path
        $firstImage = $machinery->images->first();
        $sourceImagePath = null;
        if ($firstImage && !empty($firstImage->image_path)) {
            $rawPath = ltrim($firstImage->image_path, '/');
            $fullPath = public_path($rawPath);
            if (file_exists($fullPath)) {
                $sourceImagePath = $fullPath;
            } elseif (file_exists(base_path($rawPath))) {
                $sourceImagePath = base_path($rawPath);
            }
        }

        // 3. Create canvas (1080x1080)
        $width = 1080;
        $height = 1080;
        $image = imagecreatetruecolor($width, $height);

        // Define Colors
        $bgColor = imagecolorallocate($image, 245, 247, 250);       # Crisp Off-White background
        $headerBgColor = imagecolorallocate($image, 18, 25, 38);     # Deep Navy/Dark Slate
        $accentGoldColor = imagecolorallocate($image, 235, 172, 39); # Gold Accent
        $whiteColor = imagecolorallocate($image, 255, 255, 255);
        $textColorDark = imagecolorallocate($image, 20, 20, 20);
        $badgeBgColor = imagecolorallocate($image, 220, 38, 38);      # Vibrant Red for Bid
        $footerBgColor = imagecolorallocate($image, 15, 23, 42);     # Slate Navy Footer

        // Fill background
        imagefill($image, 0, 0, $bgColor);

        // Draw Product Photo area (Middle region, 1080x760)
        $photoY = 120;
        $photoHeight = 760;

        if ($sourceImagePath && function_exists('imagecreatefromjpeg')) {
            $info = getimagesize($sourceImagePath);
            $mime = $info['mime'] ?? '';
            $srcImg = null;

            if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                $srcImg = @imagecreatefromjpeg($sourceImagePath);
            } elseif ($mime === 'image/png') {
                $srcImg = @imagecreatefrompng($sourceImagePath);
            } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                $srcImg = @imagecreatefromwebp($sourceImagePath);
            }

            if ($srcImg) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);

                // Fit aspect ratio inside 1080 x 760
                $scale = min($width / $srcW, $photoHeight / $srcH);
                $newW = (int)($srcW * $scale);
                $newH = (int)($srcH * $scale);

                $dstX = (int)(($width - $newW) / 2);
                $dstY = (int)($photoY + ($photoHeight - $newH) / 2);

                imagecopyresampled($image, $srcImg, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
                imagedestroy($srcImg);
            }
        }

        // Draw Top Header Bar (0 to 120px)
        imagefilledrectangle($image, 0, 0, $width, 120, $headerBgColor);

        // Header Gold Strip
        imagefilledrectangle($image, 0, 114, $width, 120, $accentGoldColor);

        // Draw Footer Bar (880 to 1080px)
        imagefilledrectangle($image, 0, 880, $width, $height, $footerBgColor);

        // Built-in GD fonts / TTF Text fallback
        // Top Header Title: EASTLINE AUCTIONS
        $this->drawCenteredText($image, "EASTLINE AUCTIONS", 5, 0, 35, $width, $accentGoldColor);
        $this->drawCenteredText($image, "ONLINE AUCTION CATALOG", 3, 0, 75, $width, $whiteColor);

        // Footer Text: Product Title
        $displayTitle = mb_strimwidth($productTitle, 0, 45, '...');
        $this->drawCenteredText($image, mb_strtoupper($displayTitle), 5, 0, 915, $width, $whiteColor);

        // Footer Badge: Current Bid
        $bidLabel = "CURRENT BID: " . $currentBidText;
        // Draw Bid Box
        $boxW = 500;
        $boxH = 60;
        $boxX = (int)(($width - $boxW) / 2);
        $boxY = 970;
        imagefilledrectangle($image, $boxX, $boxY, $boxX + $boxW, $boxY + $boxH, $badgeBgColor);
        $this->drawCenteredText($image, $bidLabel, 5, 0, 990, $width, $whiteColor);

        // Render JPEG Output
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=86400');
        imagejpeg($image, null, 90);
        imagedestroy($image);
        exit;
    }

    private function drawCenteredText($image, $text, $font, $x, $y, $canvasWidth, $color)
    {
        $fontWidth = imagefontwidth($font);
        $textWidth = strlen($text) * $fontWidth;
        $centerX = (int)(($canvasWidth - $textWidth) / 2);
        imagestring($image, $font, $centerX, $y, $text, $color);
    }
}
