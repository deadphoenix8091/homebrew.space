<?php

namespace HomebrewSpace;

use Elastica\Document;
use Elastica\Request;
use HomebrewSpace\Helper\GithubHelper;
use HomebrewSpace\Models\Application;
use Swoole\FastCGI\Record\Data;
use Swoole\IDEHelper\StubGenerators\SwooleLib;

class DatabaseManager {
    /** @var \Elastica\Client */
    static $elasticaClient = null;
    
    /** @var \Swoole\Lock */
    static $lock = null;

    private static function IsConnected() {
        if (self::$lock === null) self::$lock = new \Swoole\Lock(SWOOLE_MUTEX);
        self::$lock->lock();
        $returnVal = self::$elasticaClient && self::$elasticaClient->hasConnection();
        self::$lock->unlock();
        return $returnVal;
    }

    private static function Connect() {
        $elasticaClient = new \Elastica\Client(array(
            'servers' => array(
                array('host' => 'es01', 'port' => 9200),
                array('host' => 'es02', 'port' => 9200)
            )
        ));
        self::$elasticaClient = $elasticaClient;

        return true;
    }

    public static function GetIndex($indexName) {
        if (!self::IsConnected()) if (!self::Connect()) return;
        if (self::$lock === null) self::$lock = new \Swoole\Lock(SWOOLE_MUTEX);
        self::$lock->lock();
        
        $returnVal = self::$elasticaClient->getIndex($indexName);

        self::$lock->unlock();
        return $returnVal;
    }

    public static function Query($path, $query) {
        if (!self::IsConnected()) if (!self::Connect()) return;
        if (self::$lock === null) self::$lock = new \Swoole\Lock(SWOOLE_MUTEX);
        self::$lock->lock();

        $response = self::$elasticaClient->request($path, Request::GET, $query);
        
        self::$lock->unlock();
        return $response;
    }

    public static function ImportSeedData($importData) {
        if (!self::IsConnected()) if (!self::Connect()) return;
        if (self::GetIndex(Application::GetDatabaseIndexName())->exists()) {
            return;
        }

        $applications = [];
        foreach($importData as $index => $projectToImport) {
            $githubInfo = null;
            foreach($projectToImport['links'] as $currentLink)
                if ($githubInfo === null || $githubInfo === false)
                    $githubInfo = GithubHelper::ParseGithubUrl($currentLink);

            $projectInfo = $projectToImport['projectInfo'];
            $projectInfo = explode("\n", $projectInfo);
            $projectInfo = array_map(function ($currentElement) {
                return explode(": ", $currentElement)[1];
            }, $projectInfo);

            $description = $projectInfo[0];
            $tid = $projectInfo[1];

            if ($githubInfo) {
                $newApp = Application::CreateApplication(
                    $githubInfo['owner'], 
                    $githubInfo['repository'], 
                    $projectToImport['title'], 
                    $description, 
                    $tid
                );

                $newApp->Save();
            }
        }
        
        self::GetIndex(Application::GetDatabaseIndexName())->refresh();
    }
} 