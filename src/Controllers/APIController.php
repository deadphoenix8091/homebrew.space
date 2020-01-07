<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\DatabaseManager;
use HomebrewDB\ContentType;

class APIController extends BaseController {
    
    public function searchAction() {
        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        $searchQuery = '';
        if (count($urlSegments) > 2)
            $searchQuery = $urlSegments[2];
        
        $stmt = DatabaseManager::Prepare('select app.*, count(*) as "count" from app join app_categories on (app_categories.app_id = app.id) join app_releases on (app_releases.app_id = app.id) where app.state = 1 and ((app.name like :search_term OR app.description like :search_term OR app.author like :search_term) OR (app_releases.name like :search_term OR app_releases.description like :search_term OR app_releases.author like :search_term))');
        $stmt->bindValue('search_term', '%'.$searchQuery.'%');
        $stmt->execute();
        $applications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $applicationsWithReleases = [];
        foreach($applications as $key => $currentApplication) {
            $applications[$key]['url'] = '/app/' . $currentApplication['id'] . '-' . mb_strtolower($currentApplication['name']);

            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id order by prerelease asc, created_at desc limit 1');
            $stmt->bindValue('app_id', $currentApplication['id']);
            $stmt->execute();
            $release = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($release !== false) {
                $release['qr_url'] = 'https://tinydb.eiphax.tech/qr/' . $currentApplication['id'] . '/' . $release['release_id'] . '/' .$release['asset_id'] . '/QR.png';
                $release['download_url'] = 'https://tinydb.eiphax.tech/dl/' . $currentApplication['id'] . '/' . $release['release_id'] . '/' .$release['asset_id'] . '/' . $release['file_name'];
                unset($release['qr_code']);
                unset($release['app_id']);
                $applications[$key]['newest_release'] = $release;
                unset($applications[$key]['count']);
                unset($applications[$key]['state']);
                unset($applications[$key]['author']);
                unset($applications[$key]['description']);
                                         
                $this->returnJson(['success' => true, 'result' => $applications[$key]]);
            }
        }
        $this->returnJson(['success' => false]);
    }

    public function appsAction() {
        $stmt = null;
        if (isset($_REQUEST['app_id'])) {
            $stmt = DatabaseManager::Prepare('select app.*, GROUP_CONCAT(app_categories.category_id) as categories from app left join app_categories on (app_categories.app_id = app.id) where app.state = 1 and app.id = :app_id group by app.id');
            $stmt->bindValue('app_id', intval($_REQUEST['app_id']));
        } else if (isset($_REQUEST['category_id'])) {
            $stmt = DatabaseManager::Prepare('select app.*, GROUP_CONCAT(app_categories.category_id) as categories from app left join app_categories on (app_categories.app_id = app.id) where app.state = 1 and app.id in (select distinct ap.app_id from app_categories as ap where ap.category_id = :category_id) group by app.id');
            $stmt->bindValue('category_id', intval($_REQUEST['category_id']));
        } else {
            $stmt = DatabaseManager::Prepare('select app.*, GROUP_CONCAT(app_categories.category_id) as categories from app left join app_categories on (app_categories.app_id = app.id) where app.state = 1 group by app.id');
        }

        $stmt->execute();
        $applications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $applicationsAPIData = [];
        foreach($applications as $key => $currentApplication) {
            $applications[$key]['url'] = '/app/' . $currentApplication['id'] . '-' . mb_strtolower($currentApplication['name']);

            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id order by prerelease asc, created_at desc limit 1');
            $stmt->bindValue('app_id', $currentApplication['id']);
            $stmt->execute();
            $newestRelease = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = DatabaseManager::Prepare('select * from app_releases where app_id = :app_id and prerelease = 0 order by created_at asc');
            $stmt->bindValue('app_id', $currentApplication['id']);
            $stmt->execute();
            $releases = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $applicationsAPIDataEntry = [
                "id" => intval($currentApplication['id']),
                "name" => $newestRelease['name'],
                "author" => $newestRelease['author'],
                "headline" => $newestRelease['description'],
                "categories" => array_map('intval', explode(',', $currentApplication['categories'])),
                "cia" => [],
                "3dsx" => []
            ];
            foreach($releases as $currentRelease) {
               if ($currentRelease['content_type'] == ContentType::CIA) {
                    $applicationsAPIDataEntry['cia'][] = [
                        'release_id' => intval($currentRelease['release_id']),
                        'asset_id' => intval($currentRelease['asset_id']),
                        'mtime' => $currentRelease['created_at'],
                        'version' => $currentRelease['tag_name'],
                        'size' => intval($currentRelease['size']),
                        'titleid' => $currentRelease['titleid'],
                        //Yes the url shouldnt be static but I wanted to get this done quickly for now :)
                        'download_url' => 'https://tinydb.eiphax.tech/dl/'.intval($currentApplication['id']).'/'.$currentRelease['release_id'].'/'.$currentRelease['asset_id'].'/'.$currentRelease['file_name']
                    ];
               } else if ($currentRelease['content_type'] == ContentType::TDSX) {
                    $applicationsAPIDataEntry['3dsx'][] = [
                        'release_id' => intval($currentRelease['release_id']),
                        'asset_id' => intval($currentRelease['asset_id']),
                        'mtime' => $currentRelease['created_at'],
                        'version' => $currentRelease['tag_name'],
                        'size' => intval($currentRelease['size']),
                        //Yes the url shouldnt be static but I wanted to get this done quickly for now :)
                        'download_url' => 'https://tinydb.eiphax.tech/dl/'.intval($currentApplication['id']).'/'.$currentRelease['release_id'].'/'.$currentRelease['asset_id'].'/'.$currentRelease['file_name']
                    ];
                }
            }

            $applicationsAPIData[] = $applicationsAPIDataEntry;
        }

        $this->returnJson($applicationsAPIData);
    }

    public function categoriesAction() {
        $stmt = DatabaseManager::Prepare('select categories.category_id as `id`, categories.name,  count(*) as "count" from categories join app_categories on (app_categories.category_id = categories.category_id) where app_categories.app_id in (select distinct app_id from app_releases) group by categories.category_id');
        $stmt->execute();
        $allCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach($allCategories as $key => $currentCategory) {
            if ($currentCategory['id'] == 1) {
                $stmt = DatabaseManager::Prepare('select count(1) as "count" from app where state > 0 and id in (select distinct app_id from app_releases)');
                $stmt->execute();
                $allCategoryCount = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $allCategories[$key]['count'] = $allCategoryCount[0]['count'];
            }

            $allCategories[$key]['id'] = intval($allCategories[$key]['id']);
            $allCategories[$key]['count'] = intval($allCategories[$key]['count']);
        }
        $this->returnJson($allCategories);
    }

    private function returnJson($object) {
        header('Content-Type: application/json');
        echo json_encode($object);
        exit;
    }
}
