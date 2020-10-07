<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\ConfigManager;
use HomebrewSpace\DatabaseManager;
use HomebrewSpace\Models\Application;

class HomeController extends BaseController {
    protected $viewFolder = 'home';

    protected function getViewData($categoryId, $title) {
        $applications = Application::FindAll();
        $applications = array_map(function ($currentApplicationData) {
            $rawData = (new Application($currentApplicationData['_id'], $currentApplicationData['_source']))->GetRawData();
            unset($rawData['releases']);
            return $rawData;
        }, $applications['hits']['hits']);
        return $applications;
    }

    public function indexAction($request, $reponse) {
        return $this->getViewData(1, "All");
    }

    public function searchAction($request, $response) {
        $page = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
        $urlSegments = array_values(array_filter(explode('/', $page)));

        $page = $request->server['request_uri'];
        $page = array_filter(explode('?', $page))[0];
        $urlSegments = array_values(array_filter(explode('/', $page)));
        if (count($urlSegments) < 2) {
            $response->status = 404;
        }
        $searchQuery =  $urlSegments[1] . '?';

        $applications = Application::FindAll($searchQuery);
        $applications = array_map(function ($currentApplicationData) {
            $rawData = (new Application($currentApplicationData['_id'], $currentApplicationData['_source']))->GetRawData();
            unset($rawData['releases']);
            return $rawData;
        }, $applications['hits']['hits']);
        $applications = array_filter($applications, function ($currentApplicationData) {
            return count($currentApplicationData['3ds_release_files']) > 0;
        });
        return $applications;
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
