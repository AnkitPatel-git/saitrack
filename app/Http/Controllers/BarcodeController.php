<?php

namespace App\Http\Controllers;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class BarcodeController extends Controller
{
    public function generateQRCode($id)
 {

        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($id, $generator::TYPE_CODE_128);
        return Response::make($barcode, 200, ['Content-Type' => 'image/png']);
    }
}
