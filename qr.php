<?php

// Include required files
require_once 'assets/Enum/src/AbstractEnum.php';
require_once 'assets/BaconQrCode-2.0.7/src/Writer.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/RendererInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Image/ImageBackEndInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Module/ModuleInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Module/SquareModule.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Module/EdgeIterator/EdgeIterator.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Module/EdgeIterator/Edge.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Eye/EyeInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Eye/ModuleEye.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/ImageRenderer.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Image/ImagickImageBackEnd.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Image/TransformationMatrix.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/RendererStyle/RendererStyle.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/RendererStyle/Fill.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/RendererStyle/EyeFill.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Color/ColorInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Color/Gray.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Path/Path.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Path/OperationInterface.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Path/Move.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Path/Line.php';
require_once 'assets/BaconQrCode-2.0.7/src/Renderer/Path/Close.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/Encoder.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/BlockPair.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/ByteMatrix.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/QrCode.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/MaskUtil.php';
require_once 'assets/BaconQrCode-2.0.7/src/Encoder/MatrixUtil.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/ErrorCorrectionLevel.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/Mode.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/BitArray.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/Version.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/EcBlocks.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/EcBlock.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/ReedSolomonCodec.php';
require_once 'assets/BaconQrCode-2.0.7/src/Common/BitUtils.php';

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

try {

    // Check if WiFi configuration is provided via GET parameter
    $wifiConfig = isset($_GET['config']) ? $_GET['config'] : false;
     if ($wifiConfig === false) {
         throw new Exception('Wifi configuration not found');
     }

    // Decode the base64-encoded WiFi configuration
    $wifiConfig = base64_decode($wifiConfig);

    // Create QR code renderer and writer
    $renderer = new ImageRenderer(
        new RendererStyle(200),
        new ImagickImageBackEnd()
    );

    // Generate QR code image from the WiFi configuration
    $writer = new Writer($renderer);
    $qrCode = $writer->writeString($wifiConfig);

    // Set content type to PNG image
    header("Content-Type: image/png");

    // Output the QR code image
    print $qrCode;

} catch (Exception $e) {
    // Handle exceptions and display error message
    print 'Exception: ' . $e->getMessage();
    exit;
}

