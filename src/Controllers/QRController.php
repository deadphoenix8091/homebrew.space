<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\DatabaseManager;
use HomebrewDB\ContentType;

class QRController extends BaseController {
    public function indexAction() {

        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        if (count($urlSegments) != 5) {
            echo "invalid download url";
            die;
        }

        $appId = $urlSegments[1];
        $releaseId = $urlSegments[2];
        $assetId = $urlSegments[3];

        $stmt = null;
        if ($releaseId == 'latest') {
            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id and content_type = :content_type order by prerelease asc, created_at desc limit 1');
            $stmt->bindValue('content_type', intval(ContentType::strToType($assetId)));
        } else {
            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id and release_id = :rel_id and asset_id = :ass_id limit 1');
            $stmt->bindValue('rel_id', $releaseId);
            $stmt->bindValue('ass_id', $assetId);
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
