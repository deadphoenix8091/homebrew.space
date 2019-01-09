<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\DatabaseManager;

class DownloadController extends BaseController {
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
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $release['file_name'] . "\"");

            $storageFileName = __DIR__.'/../../storage/'.$appId.'-'.$release['id'].'-'.$release['file_name'];
            if (!file_exists($storageFileName)) {
                file_put_contents($storageFileName, file_get_contents($release['download_url']));
            }
            $ciaContent = file_get_contents($storageFileName);
            header("Content-Length: " . strlen($ciaContent));
            echo $ciaContent;
            exit;
        }

        echo "invalid download url";
        die;
    }
}
