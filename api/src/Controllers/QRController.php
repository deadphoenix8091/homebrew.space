<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\DatabaseManager;
use HomebrewSpace\Models\Application;

class QRController extends BaseController {
    public function indexAction($request, $response) {
        $page = $request->server['request_uri'];
        $page = array_filter(explode('?', $page))[0];
        $urlSegments = array_values(array_filter(explode('/', $page)));
        if (count($urlSegments) < 2) {
            $response->status = 404;
        }

        $application = Application::Get($urlSegments[1]);

        if ($application 
            && isset($application->latestRelease['3ds_release_files'])
            && isset($application->latestRelease['3ds_release_files'][0]) ) {
            $url = $application->latestRelease['3ds_release_files'][0]['download_url'];
            $data = (new \chillerlan\QRCode)->render($url);
            $response->header("Content-Type", "image/png");
            $response->end($data);
        } else {
            $response->status = 404;
        }
        return Application::Get($urlSegments[1])->GetRawData();// $this->getViewData(1, "All");
    }
}
