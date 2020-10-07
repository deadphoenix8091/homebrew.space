<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\CIAParser;
use HomebrewSpace\ConfigManager;
use HomebrewSpace\DatabaseManager;
use HomebrewSpace\Models\Application;

class ReleasesCronjobController extends BaseController {
    public static function updateReleases($app) {
        $githubReleasesApiUrl = 'https://api.github.com/repos/' . $app->github_owner . '/' . $app->github_repository . '/releases';
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_USERAGENT,'deadphoenix8091');
        curl_setopt($ch, CURLOPT_URL,$githubReleasesApiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: token ' . ConfigManager::GetConfiguration('github.token')
        ));
        $result=curl_exec($ch);
        curl_close($ch);
        $releasesData = json_decode($result, true);

        /*ob_start();
        var_dump($githubReleasesApiUrl);
        var_dump($releasesData);
        echo ob_get_clean().PHP_EOL;*/
        
        if (isset($releasesData['message'])) {
            return false; //No releases found.
        }

        if ($app->releases === 'releases') {
            $app->releases = array();
        }

        $latestRelease = null;
        $releases = [];

        foreach($releasesData as $currentRelease) {
            $releaseId = $currentRelease['id'];
            $saveRelease = [
                'tag_name' => $currentRelease['tag_name'],
                'name' => $currentRelease['name'],
                'published_at' => $currentRelease['published_at'],
                'prerelease' => $currentRelease['prerelease'],
                '3ds_release_files' => []
            ];

            //for now we skip the release if we already have it
            if (isset($app->releases[$releaseId])) {
                $releases[] = $app->releases[$releaseId];
                if ($latestRelease == null && $currentRelease['prerelease'] == false) {
                
                    $latestRelease = $app->latestRelease;
                }
                continue;
            }

            foreach ($currentRelease['assets'] as $currentAsset) {
                $lowerFilename = mb_strtolower($currentAsset['name']);
                if (mb_strpos($lowerFilename, '.cia') == false) continue;

                // We found a cia file in a release, lets download and try to parse it to extract metadata.
                $file = tmpfile();
                $ciaFileContent = file_get_contents($currentAsset['browser_download_url']);
                $ciaSize = strlen($ciaFileContent);
                fseek($file, 0);
                fwrite($file, $ciaFileContent, $ciaSize);
                fseek($file, 0);
                $ciaMetaData = CIAParser::GetMetadata($file, $ciaSize);
                fclose($file);
                $ciaFileContent = '';
                //@TODO: QR Code needs correct download link for current release as fileName
                //$base64QRJpeg = $this->createQRCode('dl/' . $appId . '/latest/' . $currentAsset['name'], base64_decode($ciaMetaData['images']['big']));
                if (!$ciaMetaData) continue;

                $saveRelease['3ds_release_files'][] = [
                    'cia_icon' => $ciaMetaData['images']['big'],
                    'download_url' => $currentAsset['browser_download_url'],
                    'file_size' => $ciaSize
                ];
            }

            if ($latestRelease == null && $currentRelease['prerelease'] == false) {
                
                $latestRelease = $saveRelease;
            }

            $releases[] = $saveRelease;
            
        $app->latestRelease = $latestRelease;
            $app->releases = $releases;
            $app->Save();
        }

        $app->latestRelease = $latestRelease;
        $app->releases = $releases;
    }

    public static function run() {
        $nextApplication = Application::GetNextForUpdate();
        
        if (!$nextApplication) return ['failed'];
        
        if ($nextApplication->last_updated >= microtime(true) - 60 * 60 && count($nextApplication->releases) >= 1) {
            printf("Releases cronjob is idle.\n");
            return;
        }

        printf("Releases cronjob starting for application \"%s\".\n", $nextApplication->name);

        $nextApplication->Save();
        if (!$nextApplication) return ['failed'];

        self::updateReleases($nextApplication);

        $nextApplication->Save();
        
        printf("Releases cronjob finished for application \"%s\".\n", $nextApplication->name);
    }
}



