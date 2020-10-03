<?php

namespace HomebrewSpace;

use Symfony\Component\Yaml\Yaml;

class ConfigManager {
    private static $configArray = null;
    const CONFIGURATION_FILE_PATH = __DIR__ . '/../config.yaml';

    public static function GetConfiguration($key, $defaultValue = '') {
        //Key is dot seperated for levels
        $transformedKey = mb_strtoupper(preg_replace('/\./', '_', $key));
        if (isset($_ENV[$transformedKey])) {
            return $_ENV[$transformedKey];
        }

        $returnValue = $defaultValue;
        if (self::$configArray === null) {
            //Load config from yaml file

            if (file_exists(self::CONFIGURATION_FILE_PATH)) {
                self::$configArray = Yaml::parseFile(self::CONFIGURATION_FILE_PATH);
            } else {
                self::$configArray = [];
            }
        }

        $currentVal = self::$configArray;
        $foundKey = false;
        $keySegments = array_values(array_filter(explode('.', $key)));
        foreach($keySegments as $idx => $currentKeySegment) {
            if (!isset($currentVal[$currentKeySegment])) break;

            $currentVal = $currentVal[$currentKeySegment];
            if ($idx == count($keySegments) -1) $foundKey = true;
        }

        if ($foundKey) $returnValue = $currentVal;

        return $returnValue;
    }
}