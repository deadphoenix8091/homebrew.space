<?php

namespace HomebrewSpace\Controllers;

use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use HomebrewSpace\BaseController;
use HomebrewSpace\DatabaseManager;
use HomebrewSpace\Models\Application;

class QRController extends BaseController {
    public function indexAction($request, \Swoole\Http\Response $response) {
        $page = $request->server['request_uri'];
        $page = array_filter(explode('?', $page))[0];
        $urlSegments = array_values(array_filter(explode('/', $page)));
        if (count($urlSegments) < 2) {
            $response->status = 404;
        }

        $application = Application::Get($urlSegments[1]);

        if ($application 
            && isset($application->latestRelease['3ds_release_files'])
            && count($application->latestRelease['3ds_release_files']) > 0 ) {
            $url = $application->latestRelease['3ds_release_files'][0]['download_url'];
            $myOptions = [
                'version'         => 5,
                'eccLevel'        => QRCode::ECC_L,
                'outputType'      => QRCode::OUTPUT_CUSTOM,
                'outputInterface' => QRImagick::class
             ];
            
            // extends QROptions
            $myCustomOptions = new QROptions($myOptions);
            $data = (new QRCode($myCustomOptions))->render($url);
            $response->header("Content-Type", "image/png");
            $response->end($data);
        } else {
            $response->status = 404;
            $response->end("");
        }
    }
}
