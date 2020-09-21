<?php
namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use Swoole\Http\Request;
use Swoole\Http\Response;

class StaticFileController extends BaseController {
    private static $static = [
        'css'  => 'text/css',
        'js'   => 'text/javascript',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpg',
        'jpeg' => 'image/jpg',
        'mp4'  => 'video/mp4',
        'eot' => 'application/vnd.ms-fontobject',
        'svg' => 'image/svg+xml',
        'ttf' => 'application/font-ttf',
        'woff' => 'application/font-woff',
        'woff2' => 'font/woff2'
    ];

    public static function handleStaticFile(Request $request, Response $response) {
        echo realpath(__DIR__.'../../resources/public/');
        $staticFile = __DIR__ . $request->server['request_uri'];
        if (!file_exists($staticFile)) {
            return false;
        }
        $type = pathinfo($staticFile, PATHINFO_EXTENSION);
        if (!isset($static[$type])) {
            return false;
        }
        $response->header('Content-Type', $static[$type]);
        $response->sendfile($staticFile);
        return true;
    }
}