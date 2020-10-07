<?php
namespace HomebrewSpace\Helper;

class GithubHelper {
    public static function ParseGithubUrl($githubUrl) {
        $urlParts = parse_url($githubUrl);

        if (!isset($urlParts['host']) || !in_array($urlParts['host'], ['www.github.com', 'github.com'])) return false;

        $pathSegments = array_filter(explode('/', $urlParts['path']));
        if (count($pathSegments) < 2) {
            return false;
        }

        return ['owner' => $pathSegments[1], 'repository' => $pathSegments[2]];
    }
}