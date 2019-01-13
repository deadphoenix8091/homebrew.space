<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;
use HomebrewDB\ConfigManager;
use HomebrewDB\DatabaseManager;

class HomeController extends BaseController {
    protected $viewFolder = 'home';

    protected function getViewData($categoryId, $title) {
        $stmt = null;
        if ($categoryId == 1 || $categoryId == -1) {
            $stmt = DatabaseManager::Prepare('select app.* from app where app.state = 1 group by app.id');
        } else {
            $stmt = DatabaseManager::Prepare('select app.* from app join app_categories on (app_categories.app_id = app.id) where app.state = 1 and  app_categories.category_id = :category_id group by app.id');
            $stmt->bindValue(':category_id', $categoryId);
        }

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
                $applications[$key]['newest_release'] = $release;
                $applicationsWithReleases[$key] = $applications[$key];
            }
        }

        $applications = $applicationsWithReleases;

        return [
            'title' => "Applications in Category \"" . $title . "\"",
            "applications" => $applications
        ];
    }

    public function indexAction() {
        return $this->getViewData(1, "All");
    }

    public function searchAction() {
        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        $searchQuery = '';
        if (count($urlSegments) > 1)
            $searchQuery = $urlSegments[1];

        $stmt = DatabaseManager::Prepare('select app.*, count(*) as "count" from app join app_categories on (app_categories.app_id = app.id) where app.state = 1 and (app.name like :search_term OR app.description like :search_term OR app.author like :search_term) group by app.id');
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
                $applications[$key]['newest_release'] = $release;
                $applicationsWithReleases[$key] = $applications[$key];
            }
        }

        $applications = $applicationsWithReleases;

        return [
            'title' => "Search Results for \"" . $searchQuery . "\"",
            "applications" => $applications
        ];
    }

    public function creditsAction() {
    }

    public function rulesAction() {
        return [];
    }

    public function categoryAction() {
        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        $categoryId = -1;
        if (count($urlSegments) > 1 && count(explode('-', $urlSegments[1])) > 1)
            $categoryId = intval(explode('-', $urlSegments[1])[0]);

        if ($categoryId === -1) {
            $this->Redirect('/');
        }

        $stmt = DatabaseManager::Prepare('select * from categories where categories.category_id = :category_id');
        $stmt->bindValue(':category_id', $categoryId);
        $stmt->execute();
        $matchedCategorys = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (count($matchedCategorys) < 1) {
            $this->Redirect('/');
        }

        return $this->getViewData($categoryId, $matchedCategorys[0]['name']);
    }
}
