<?php

namespace HomebrewDB;

class TemplateGlobals {
    private static function FetchCategories() {
        $stmt = DatabaseManager::Prepare('select * from categories');
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