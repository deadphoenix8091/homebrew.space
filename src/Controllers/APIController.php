<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\DatabaseManager;

class APIController extends BaseController {
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
                "tdsx" => []
            ];
            foreach($releases as $currentRelease) {
                $applicationsAPIDataEntry['cia'][] = [
                    'id' => intval($currentRelease['id']),
                    'mtime' => $currentRelease['created_at'],
                    'version' => $currentRelease['tag_name'],
                    'size' => intval($currentRelease['size']),
                    'titleid' => $currentRelease['titleid'],
                    //Yes the url shouldnt be static but I wanted to get this done quickly for now :)
                    'download_url' => 'https://tinydb.eiphax.tech/dl/'.intval($currentApplication['id']).'/'.intval($currentRelease['id']).'/'.$currentRelease['file_name']
                ];
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