<?php

namespace HomebrewSpace\Models;

/**
 * @property string $github_owner
 * @property string $github_repository
 * @property bool $published
 * @property string[] $tags
 */
class Application extends BaseModel {
    /**
     * Creates and persists a new application entry given a github url and a set of tags.
     * @return Application
     */
    public static function CreateApplication($github_owner, $github_repository, $tags) {
        $app = new Application([]);
        $app->github_owner = $github_owner;
        $app->github_repository = $github_repository;
        $app->tags = $tags;
        $app->published = false;
        $app->Save();
        return $app;
    } 
}