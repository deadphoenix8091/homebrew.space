<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\CIAParser;
use HomebrewDB\ConfigManager;
use HomebrewDB\DatabaseManager;

class ReleasesCronjobController extends BaseController {
    public function updateReleases($app) {
        $appId = $app['id'];
        $githubRepoUrl = $app['github_url'];
        $urlParts = parse_url($githubRepoUrl);
        $pathSegments = array_filter(explode('/', $urlParts['path']));
        if (count($pathSegments) < 2) {
            return false;
        }
        $githubReleasesApiUrl = 'https://api.github.com/repos/' . $pathSegments[1] . '/' . $pathSegments[2] . '/releases?access_token=' . ConfigManager::GetConfiguration('github.token');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'deadphoenix8091');
        curl_setopt($ch, CURLOPT_URL,$githubReleasesApiUrl);
        $result=curl_exec($ch);
        curl_close($ch);
        $releasesData = json_decode($result, true);

        if (isset($releasesData['message'])) {
            return false; //No releases found.
        }

        foreach($releasesData as $currentRelease) {
            $stmt = DatabaseManager::Prepare('select count(1) as found_releases from app_releases where id = :release_id');
            $stmt->bindValue('release_id', $currentRelease['id']);
            $stmt->execute();
            if (intval($stmt->fetch()['found_releases']) > 0) {
                continue; //We already got this release, no need to fetch everything again
            }

            foreach ($currentRelease['assets'] as $currentAsset) {
                $lowerFilename = mb_strtolower($currentAsset['name']);
                if (mb_strpos($lowerFilename, '.cia') == false) continue;

                $file = tmpfile();
                $ciaFileContent = file_get_contents($currentAsset['browser_download_url']);
                $ciaSize = strlen($ciaFileContent);
                fseek($file, 0);
                fwrite($file, $ciaFileContent, $ciaSize);
                fseek($file, 0);
                $ciaMetaData = CIAParser::GetMetadata($file);
                fclose($file);
                //@TODO: QR Code needs correct download link for current release as fileName
                $base64QRJpeg = $this->createQRCode('dl/' . $appId . '/latest/' . $currentAsset['name'], base64_decode($ciaMetaData['images']['big']));
                $ciaFileContent = '';

                $stmt = DatabaseManager::Prepare('insert into app_releases (`id`, `file_name`, `download_url`, `tag_name`, `prerelease`, `name`, `description`, `author`, `titleid`, `size`, `app_id`, `qr_code`, `created_at`)'.
                    ' values (:id, :file_name, :download_url, :tag_name, :prerelease, :name, :description, :author, :titleid, :ciasize, :app_id, :qr_code, :created_at)');
                $stmt->bindValue('id', $currentRelease['id']);
                $stmt->bindValue('file_name', $currentAsset['name']);
                $stmt->bindValue('download_url', $currentAsset['browser_download_url']);
                $stmt->bindValue('tag_name', $currentRelease['tag_name']);
                $stmt->bindValue('prerelease', $currentRelease['prerelease']);
                $stmt->bindValue('app_id', $appId);
                $stmt->bindValue('qr_code', $base64QRJpeg);
                $stmt->bindValue('created_at', $currentRelease['created_at']);
                $stmt->bindValue('name', $ciaMetaData['name']);
                $stmt->bindValue('author', $ciaMetaData['publisher']);
                $stmt->bindValue('ciasize', $ciaSize);
                $stmt->bindValue('titleid', $ciaMetaData['title_id']);
                $stmt->bindValue('description', $ciaMetaData['description']);
                $stmt->execute();

                break;//Only 1 cia file per release, we just take the first one
            }
        }
    }

    private function createQRCode($fileName, $imageData) {
        $data = 'https://tinydb.eiphax.tech/' . $fileName;
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
        ob_start ();
        imagepng ($QR);
        $qrRaw = ob_get_contents ();
        ob_end_clean ();
        imagedestroy($QR);
        return base64_encode($qrRaw);
    }

    public function indexAction() {
        ini_set('max_execution_time', 0);
        //Get the app/submission that is waiting the longest for a release scan (minimum wait time 1 hour)
        $stmt = DatabaseManager::Prepare('select * from app where state > 0 and last_release_scan_at is null or last_release_scan_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) order by last_release_scan_at asc limit 1');
        $stmt->execute();
        $apps = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($apps) != 1) { echo "no work"; exit; }

        $stmt = DatabaseManager::Prepare('update app set last_release_scan_at = NOW() where id = :app_id');
        $stmt->bindValue('app_id', $apps[0]['id']);
        $stmt->execute();

        $this->updateReleases($apps[0]);

        echo "ok";
        exit;
        //header("Content-type: application/json; charset=utf-8");
        //echo json_encode(CIAParser::GetMetadata('FBI.cia'));
        //exit;
    }
}



