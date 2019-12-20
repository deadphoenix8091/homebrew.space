<?php

namespace HomebrewDB;

class TDSXParser {
    private static function blockAlign($dataSize, $blockSize) {
        if ($dataSize % $blockSize == 0)
            return $dataSize;

        return $dataSize + $blockSize - ($dataSize % $blockSize);
    }

    private static function createcolor($image,$c1,$c2,$c3) {
        //get color from palette
        $color = imagecolorexact($image, $c1, $c2, $c3);
        if($color==-1) {
            //color does not exist...
            //test if we have used up palette
            if(imagecolorstotal($image)>=255) {
                //palette used up; pick closest assigned color
                $color = imagecolorclosest($image, $c1, $c2, $c3);
            } else {
                //palette NOT used up; assign new color
                $color = imagecolorallocate($image, $c1, $c2, $c3);
            }
        }
        return $color;
    }

    private static function readPixel($fileHandle, $image){
        $rgb565PixelData = bin2hex(strrev(fread($fileHandle, 2)));
        $rgb565PixelData = str_pad(base_convert($rgb565PixelData, 16, 2), 16, '0', STR_PAD_LEFT);
        $red = substr($rgb565PixelData, 0, 5);
        $red = (int)round((bindec($red) / (pow(2, 5) - 1)) * 255);
        $green = substr($rgb565PixelData, 5, 6);
        $green = (int)round((bindec($green) / (pow(2, 6) - 1)) * 255);
        $blue = substr($rgb565PixelData, 11, 5);
        $blue = (int)round((bindec($blue) / (pow(2, 5) - 1)) * 255);
        return self::createcolor($image, $red, $green, $blue);
    }

    private static function read8x8Tile($image, $fileHandle, $dstX, $dstY) {
        for($tileOuterY = 0; $tileOuterY < 2; $tileOuterY++) {
            for($tileOuterX = 0; $tileOuterX < 2; $tileOuterX++) {
                for($tileInnerY = 0; $tileInnerY < 2; $tileInnerY++) {
                    for($tileInnerX = 0; $tileInnerX < 2; $tileInnerX++) {
                        $rgb565PixelData = self::readPixel($fileHandle, $image);
                        imagesetpixel($image, $dstX + $tileOuterX * 4 + $tileInnerX * 2 + 0, $dstY + $tileOuterY * 4 + $tileInnerY * 2 + 0, $rgb565PixelData);
                        $rgb565PixelData = self::readPixel($fileHandle, $image);
                        imagesetpixel($image, $dstX + $tileOuterX * 4 + $tileInnerX * 2 + 1, $dstY + $tileOuterY * 4 + $tileInnerY * 2 + 0, $rgb565PixelData);
                        $rgb565PixelData = self::readPixel($fileHandle, $image);
                        imagesetpixel($image, $dstX + $tileOuterX * 4 + $tileInnerX * 2 + 0, $dstY + $tileOuterY * 4 + $tileInnerY * 2 + 1, $rgb565PixelData);
                        $rgb565PixelData = self::readPixel($fileHandle, $image);
                        imagesetpixel($image, $dstX + $tileOuterX * 4 + $tileInnerX * 2 + 1, $dstY + $tileOuterY * 4 + $tileInnerY * 2 + 1, $rgb565PixelData);
                    }
                }
            }
        }
    }

    public static function GetMetadata($fileHandle) {
        $tdsxMagic = fread($fileHandle, 4);
        if ($tdsxMagic !== '3DSX') {
            echo 'Err: magic ='.$tdsxMagic;
            return NULL;
        }
        $headerSize = unpack('V', fread($fileHandle, 4))[1];
        if ($headerSize < 32) {
            echo 'Err: Extended header required';
            return NULL;
        }
        fseek($fileHandle, 32);
        $smdhFileOffset = unpack('V', fread($fileHandle, 4))[1];
        $sdmhSize = unpack('V', fread($fileHandle, 4))[1];
        fseek($fileHandle, $smdhFileOffset + 0x8 + 0x200);
        $englishShortDescription = str_replace("\00", '', mb_convert_encoding(fread($fileHandle, 0x80), "UTF-8", "UTF-16LE"));
        $englishLongDescription = str_replace("\00", '', mb_convert_encoding(fread($fileHandle, 0x100), "UTF-8", "UTF-16LE"));
        $englishPublisher = str_replace("\00", '', mb_convert_encoding(fread($fileHandle, 0x80), "UTF-8", "UTF-16LE"));

        $smallIcon = imagecreate(24, 24);
        fseek($fileHandle, $smdhFileOffset + 0x2040);


        for($y = 0; $y < 3; $y++) {
            for($x = 0; $x < 3; $x++) {
                self::read8x8Tile($smallIcon, $fileHandle, $x * 8, $y * 8);
            }
        }
        $bigIcon = imagecreate(48, 48);
        fseek($fileHandle, $smdhFileOffset + 0x24C0);
        for($y = 0; $y < 6; $y++) {
            for($x = 0; $x < 6; $x++) {
                self::read8x8Tile($bigIcon, $fileHandle, $x * 8, $y * 8);
            }
        }

        ob_start ();
        imagejpeg ($smallIcon);
        imagedestroy ($smallIcon);
        $smallIconRawJPEG = ob_get_contents ();
        ob_end_clean ();

        ob_start ();
        imagejpeg ($bigIcon);
        imagedestroy ($bigIcon);
        $bigIconRawJPEG = ob_get_contents ();
        ob_end_clean ();

        return [
            'name' => $englishShortDescription,
            'description' => $englishLongDescription,
            'publisher' => $englishPublisher,
            'title_id' => 0,
            'images' => [
                'small' => base64_encode($smallIconRawJPEG),
                'big' => base64_encode($bigIconRawJPEG)
            ]
        ];
    }
}
