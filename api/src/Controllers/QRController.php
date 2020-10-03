<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\DatabaseManager;

class QRController extends BaseController {
    public function indexAction() {

        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        if (count($urlSegments) != 4) {
            echo "invalid download url";
            die;
        }

        $appId = $urlSegments[1];
        $releaseId = $urlSegments[2];

        $stmt = null;
        if ($releaseId == 'latest') {
            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id order by prerelease asc, created_at desc limit 1');
        } else {
            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id and id = :release_id limit 1');
            $stmt->bindValue('release_id', $releaseId);
        }

        $stmt->bindValue('app_id', $appId);
        $stmt->execute();
        $release = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($release !== false) {
            header('Content-Type: image/png');
            header("Content-Transfer-Encoding: Binary");
            header("Cache-Control: max-age=2592000"); //30 days max age
            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 30))); // 30 days
            $qrRaw = base64_decode($release['qr_code']);
            header("Content-Length: " . strlen($qrRaw));
            echo $qrRaw;
            exit;
            
        }

        echo "invalid qr url";
        die;
    }
}
