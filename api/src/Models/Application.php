<?php

namespace HomebrewSpace\Models;

use HomebrewSpace\DatabaseManager;

/**
 * @property string $github_owner
 * @property string $github_repository
 * @property string $name
 * @property string $description
 * @property string $title_id
 * @property bool $published
 * @property string[] $tags
 */
class Application extends BaseModel {
    public static function CreateApplication($github_owner, $github_repository, $name, $description, $tid, $tags = [])
    {
        return new Application(null, [
            'github_owner' => $github_owner,
            'github_repository' => $github_repository,
            'name' => $name,
            'description' => $description,
            'title_id' => $tid,
            'tags' => $tags,
            'published' => false,
            'latestRelease' => array('published_at' => null)
        ]);
    }

    public static function GetNextForUpdate() {
        $query = array(
            "sort" => array(
                array("last_updated" => array("order" => "asc"))
            ),
            'query' => array(
                'query_string' => array(
                    'query' => '*',
                )
            )
        );
        
        $path = self::GetDatabaseIndexName() . '/_search?size=1';
        
        try {
            $response = DatabaseManager::Query($path, $query);
        } catch (\Swoole\Error $th) {
            return false;
        }
       
        $responseArray = $response->getData();

        if (count($responseArray['hits']['hits']) < 1) return false;

        return new Application($responseArray['hits']['hits'][0]['_id'], $responseArray['hits']['hits'][0]['_source']);
    }
}