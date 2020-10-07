<?php

namespace HomebrewSpace\Controllers;

use HomebrewSpace\BaseController;
use HomebrewSpace\ConfigManager;
use HomebrewSpace\DatabaseManager;
use HomebrewSpace\Models\Application;

class ApplicationsController extends BaseController {
    protected $viewFolder = 'home';

    /**
     * @param $reponse \Swoole\Http\Response 
     */
    public function detailAction($request, $response) {
        $page = $request->server['request_uri'];
        $page = array_filter(explode('?', $page))[0];
        $urlSegments = array_values(array_filter(explode('/', $page)));
        if (count($urlSegments) < 2) {
            $response->status = 404;
        }
        return Application::Get($urlSegments[1])->GetRawData();// $this->getViewData(1, "All");
    }
}
