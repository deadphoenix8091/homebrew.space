<?php

namespace HomebrewDB;

class CIAParser {
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
        $archiveHeaderSize = unpack('V', fread($fileHandle, 4))[1];
        fseek($fileHandle, 0);

        $ciaHeader = fread($fileHandle, $archiveHeaderSize);
        $ciaHeaderData = unpack('Vheader_size/vtype/vversion/Vcert_chain_size/Vticket_size/Vtmd_file_size/Vmeta_size/V2content_size/h*content_index', $ciaHeader);

        $tmdStartOffset =
            self::blockAlign($ciaHeaderData['header_size'], 0x40) +
            self::blockAlign($ciaHeaderData['cert_chain_size'], 0x40) +
            self::blockAlign($ciaHeaderData['ticket_size'], 0x40);

// Parsing TMD Data
        fseek($fileHandle, $tmdStartOffset);
// ---- Signature Data
        $signatureType = fread($fileHandle, 4);
        $signatureSizes = [
            hex2bin('00010000') => 0x200,
            hex2bin('00010001') => 0x100,
            hex2bin('00010002') => 0x3C,
            hex2bin('00010003') => 0x200,
            hex2bin('00010004') => 0x100,
            hex2bin('00010005') => 0x3C,
        ];
        $signatureDataSize = $signatureSizes[$signatureType];

        fseek($fileHandle, $tmdStartOffset + self::blockAlign($signatureDataSize + 4, 0x40));
        $tmdHeader = fread($fileHandle, 0xC4);
        $tmdHeaderData = unpack(
            'h128signature_issuer/' .
            'h1version/' .
            'h1ca_crl_version/' .
            'h1signer_crl_version/' .
            'h1reserved/' .
            'H16system_version/' .
            'H16title_id/' .
            'Ntitle_type/' .
            'ngroup_id/' .
            'Nsave_data_size/' .
            'Nsrl_private_size/' .
            'Nreserved/' .
            'hsrl_flag/' .
            'H98reserved2/' .
            'Naccess_rights/' .
            'ntitle_version/' .
            'ncontent_count/' .
            'nboot_content'
            , $tmdHeader);

        fseek($fileHandle, $tmdStartOffset + self::blockAlign($ciaHeaderData['tmd_file_size'] + 4, 0x40) + 0x1A0);
        $exeFsOffset = unpack('V', fread($fileHandle, 4))[1];
        $exeFsSize = unpack('V', fread($fileHandle, 4))[1];

        $exefsFileMap = [];

        for($i = 0; $i < 10; $i++) {
            fseek($fileHandle, $tmdStartOffset + self::blockAlign($ciaHeaderData['tmd_file_size'] + 4, 0x40) + $exeFsOffset * 0x200 + $i * 16);
            $fileName = str_replace("\00", '', fread($fileHandle, 8));
            $fileOffset = unpack('V', fread($fileHandle, 4))[1];
            $fileSize = unpack('V', fread($fileHandle, 4))[1];

            if ($fileSize > 0) {
                $exefsFileMap[$fileName] = [
                    'name' => $fileName,
                    'offset' => $fileOffset,
                    'size' => $fileSize
                ];
            }
        }

// Getting the SMDH and Parsing it!!! :)
        $smdhFileOffset = $tmdStartOffset + self::blockAlign($ciaHeaderData['tmd_file_size'] + 4, 0x40) + $exeFsOffset * 0x200 + 0x200 + $exefsFileMap['icon']['offset'];
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
            'title_id' => $tmdHeaderData['title_id'],
            'images' => [
                'small' => base64_encode($smallIconRawJPEG),
                'big' => base64_encode($bigIconRawJPEG)
            ]
        ];
    }
}