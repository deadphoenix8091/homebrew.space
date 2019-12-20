<?php
namespace HomebrewDB\Controllers;
use HomebrewDB\BaseController;
use HomebrewDB\CIAParser;
use HomebrewDB\TDSXParser;
use HomebrewDB\ConfigManager;
use HomebrewDB\DatabaseManager;
use HomebrewDB\ContentType;

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
        curl_setopt($ch,CURLOPT_USERAGENT,ConfigManager::GetConfiguration('github.username'));
        curl_setopt($ch, CURLOPT_URL,$githubReleasesApiUrl);
        $result=curl_exec($ch);
        curl_close($ch);
        $releasesData = json_decode($result, true);
        if (isset($releasesData['message'])) {
            return false; //No releases found.
        }
        foreach($releasesData as $currentRelease) {
            echo "Hello:<br/>";

            foreach ($currentRelease['assets'] as $currentAsset) {
                $stmt = DatabaseManager::Prepare('select count(1) as found_releases from app_releases where id = :release_id');
                $stmt->bindValue('release_id', $currentAsset['id']);
                $stmt->execute();
                if (intval($stmt->fetch()['found_releases']) > 0) {
                    continue; //We already got this release, no need to fetch everything again
                }

                $lowerFilename = mb_strtolower($currentAsset['name']);
                if (mb_strpos($lowerFilename, '.cia') !== false) {
                    $contentType = ContentType::CIA;
                } else if (mb_strpos($lowerFilename, '.3dsx') !== false) {
                    $contentType = ContentType::TDSX;
                } else {
                    continue;
                }

		echo $currentAsset['name']."<br/>";

                $file = tmpfile();
                $fileContent = file_get_contents($currentAsset['browser_download_url']);
                $contentSize = strlen($fileContent);
                fseek($file, 0);
                fwrite($file, $fileContent, $contentSize);
                fseek($file, 0);
                if ($contentType == ContentType::CIA) {
                    $appMetaData = CIAParser::GetMetadata($file);
                } else if ($contentType == ContentType::TDSX) {
                    $appMetaData = TDSXParser::GetMetadata($file);
                }
                fclose($file);
                //@TODO: QR Code needs correct download link for current release as fileName
                $base64QRJpeg = $this->createQRCode('dl/' . $appId . '/latest/' . $currentAsset['name'], base64_decode($appMetaData['images']['big']));
                $fileContent = '';
                $stmt = DatabaseManager::Prepare('insert into app_releases (`id`, `content_type`, `file_name`, `download_url`, `tag_name`, `prerelease`, `name`, `description`, `author`, `titleid`, `size`, `app_id`, `qr_code`, `created_at`)'.
                        ' values (:id, :content_type, :file_name, :download_url, :tag_name, :prerelease, :name, :description, :author, :titleid, :content_size, :app_id, :qr_code, :created_at)');
                try {
                    $stmt->bindValue('id', $currentAsset['id']);
                    $stmt->bindValue('content_type', $contentType);
                    $stmt->bindValue('file_name', $currentAsset['name']);
                    $stmt->bindValue('download_url', $currentAsset['browser_download_url']);
                    $stmt->bindValue('tag_name', $currentRelease['tag_name']);
                    $stmt->bindValue('prerelease', 0);
                    $stmt->bindValue('app_id', $appId);
                    $stmt->bindValue('qr_code', $base64QRJpeg);
                    $stmt->bindValue('created_at', date("Y-m-d H:i:s", strtotime($currentAsset['created_at'])));
                    $stmt->bindValue('name', $appMetaData['name']);
                    $stmt->bindValue('author', $appMetaData['publisher']);
                    $stmt->bindValue('content_size', $contentSize);
                    $stmt->bindValue('titleid', $appMetaData['title_id']);
                    $stmt->bindValue('description', $appMetaData['description']);
                    $result = $stmt->execute();
                }
                catch(PDOException $e) {
                    echo '<pre>';
                    echo $selectQuery;
                    echo '</pre>';
                    echo $e->getMessage();
                    die;
                }
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
        $stmt = DatabaseManager::Prepare('select * from app where state > 0 order by last_release_scan_at asc limit 1');
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


