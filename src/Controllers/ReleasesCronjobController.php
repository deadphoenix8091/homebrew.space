<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\CIAParser;

class ReleasesCronjobController extends BaseController {
    public function getReleases($githubRepoUrl) {
        $urlParts = parse_url($githubRepoUrl);
        $githubReleasesApiUrl = 'https://api.github.com/repos' . $urlParts['path'] . '/releases';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'deadphoenix8091');
        curl_setopt($ch, CURLOPT_URL,$githubReleasesApiUrl);
        $result=curl_exec($ch);
        curl_close($ch);
        $releasesData = json_decode($result, true);

        $releasesCount = count($releasesData);

        if ($releasesCount == 0) {
            return false; //No releases found.
        }

        foreach($releasesData as $currentRelease) {
            $preRelease = $currentRelease['prerelease'];

            if ($preRelease) continue; //@TODO: Determine weither or not to allow pre-releases???

            $tagName = $currentRelease['tag_name'];
            foreach ($currentRelease['assets'] as $currentAsset) {
                $lowerFilename = mb_strtolower($currentAsset['name']);
                if (mb_strpos($lowerFilename, '.cia') == false) continue;

                //We are a cia file... download if doesnt exist :)
                if (!file_exists('storage/' . $currentAsset['id'] . '.cia')) {
                    $ciaFileContent = file_get_contents($currentAsset['browser_download_url']);
                    file_put_contents('storage/' . $currentAsset['id'] . '.cia', $ciaFileContent);
                    //$ciaMetaData = CIAParser::GetMetadata('storage/' . $currentAsset['id'] . '.cia');
                    //$this->createQRCode('storage/' . $currentAsset['id'] . '.cia', base64_decode($ciaMetaData['images']['big']), 'storage/' . $currentAsset['id'] . '.qr.png');
                    $ciaFileContent = '';
                    die;
                }

                $ciaMetaData = CIAParser::GetMetadata('storage/ftpd.cia');
                $this->createQRCode('storage/' . $currentAsset['id'] . '.cia', base64_decode($ciaMetaData['images']['big']), 'storage/' . $currentAsset['id'] . '.qr.png');

            }
        }

        die;
    }

    private function createQRCode($fileName, $imageData, $outputFileName) {
        $data = 'http://localhost/' . $fileName;
        $size = '200x200';
        $logo = true;
// Get QR Code image from Google Chart API
// http://code.google.com/apis/chart/infographics/docs/qr_codes.html
        $QR = imagecreatefrompng('https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs='.$size.'&chl='.urlencode($data));
        if($logo !== FALSE){
            $logo = imagecreatefromstring($imageData);
            $QR_width = imagesx($QR);
            $QR_height = imagesy($QR);

            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);

            // Scale logo to fit in the QR Code
            $logo_qr_width = $QR_width/3;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;

            imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        }
        imagepng($QR, $outputFileName);
        imagedestroy($QR);
    }

    public function indexAction() {
        //$githubUrl = 'https://api.github.com/repos/Steveice10/FBI/releases';
        //$githubUrl = 'https://github.com/KunoichiZ/lumaupdate';
        $githubUrl = 'https://github.com/FlagBrew/Checkpoint';

        $this->getReleases($githubUrl);

        header("Content-type: application/json; charset=utf-8");
        echo json_encode(CIAParser::GetMetadata('FBI.cia'));
        exit;
    }
}