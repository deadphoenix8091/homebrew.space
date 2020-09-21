<?php

namespace HomebrewSpace;

class TemplateGlobals {
    private static function FetchCategories() {
        $stmt = DatabaseManager::Prepare('select categories.*, count(*) as "count" from categories join app_categories on (app_categories.category_id = categories.category_id) where app_categories.app_id in (select distinct app_id from app_releases) group by categories.category_id');
        $stmt->execute();
        $allCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach($allCategories as $key => $currentCategory) {
            if ($currentCategory['category_id'] == 1) {
                $stmt = DatabaseManager::Prepare('select count(1) as "count" from app where state > 0 and id in (select distinct app_id from app_releases)');
                $stmt->execute();
                $allCategoryCount = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $allCategories[$key]['count'] = $allCategoryCount[0]['count'];
            }
            $allCategories[$key]['url'] = '/category/' . $currentCategory['category_id'] . '-' . mb_strtolower($currentCategory['name']);
        }

        return $allCategories;
    }

    public static function BuildGlobals() {
        return [
            'categories' => self::FetchCategories()
        ];
    }
}