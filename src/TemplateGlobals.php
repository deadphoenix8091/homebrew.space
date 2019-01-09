<?php

namespace HomebrewDB;

class TemplateGlobals {
    private static function FetchCategories() {
        $stmt = DatabaseManager::Prepare('select categories.*, count(*) as "count" from categories join app_categories on (app_categories.category_id = categories.category_id) group by categories.category_id');
        $stmt->execute();
        $allCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach($allCategories as $key => $currentCategory) {
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