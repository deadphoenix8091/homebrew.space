<?php

namespace HomebrewSpace\Models;

use Elastica\Document;
use Exception;
use HomebrewSpace\DatabaseManager;

class BaseModel {
    protected $_data;
    protected $_identifier;
    
    public function __construct($identifier = null, $data = null)
    {
        if ($identifier === null) {
            $identifier = $this->generateNewId();
        }
        if (!is_array($data)) {
            $data = [];
        }
        $this->_identifier = $identifier;
        $this->_data = $data;
    }

    public function __get($name) {
        if (isset($this->_data[$name])) return $this->_data[$name];

        return $name;
    }

    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function GetID() {
        return $this->_identifier;
    }

    public function ToJSON() {
        return json_encode($this->_data);
    }

    public function Save() {
        $index = DatabaseManager::GetIndex(self::GetDatabaseIndexName());
        $this->last_updated = microtime(true);
        
        if (DatabaseManager::$lock === null) DatabaseManager::$lock = new \Swoole\Lock(SWOOLE_MUTEX);
        DatabaseManager::$lock->lock();

        $index->addDocument(new Document($this->_identifier, $this->_data));
        
        DatabaseManager::$lock->unlock();
        //$index->refresh();
    }

    public static function Get($id) {
        $index = DatabaseManager::GetIndex(self::GetDatabaseIndexName());
        return new self($id, $index->getDocument($id)->getData());
    }

    public function GetRawData() {
        $this->_data['id'] = $this->_identifier;
        return $this->_data;
    }

    public static function FindAll($searchTerm = null):array {
        $query = array(
            "sort" => array(
                array("latestRelease.published_at" => array("order" => "desc"))
            ),
            'query' => array(
                'query_string' => array(
                    'query' => '*',
                )
            )
        );
        if ($searchTerm) {
            $query = array(
                'query' => array(
                    /*"fuzzy" => array(
                        "name" => array(
                            "value" => $searchTerm,
                            "fuzziness" => "AUTO:1,6",
                            "max_expansions" => 200,
                            "prefix_length" => 0,
                            "transpositions" => true,
                            "rewrite" => "constant_score"
                        )
                    )*/
                    "multi_match" => array(
                        "query"=>      $searchTerm,
                        "type" =>       "best_fields",
                        "fields" =>     [ "name", "description", "github_repository", "releases.name", "releases.tag_name" ],
                        "tie_breaker" => 0.3
                    )
                )
            );
        }
        
        $path = self::GetDatabaseIndexName() . '/_search?size=10000';
        
        $response = DatabaseManager::Query($path, $query);
        $responseArray = $response->getData();

        return $responseArray;
    }

    public static function GetDatabaseIndexName() {
        return preg_replace("/[^A-Za-z0-9 ]/", '', mb_strtolower(get_called_class()));
    }

    private function generateNewId() {
        return uniqid();
    }
}