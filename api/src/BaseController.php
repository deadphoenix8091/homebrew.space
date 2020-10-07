<?php

namespace HomebrewSpace;


abstract class BaseController {
    /** @var Router */
    protected $router;

    protected $viewFolder = '.';

    /**
     * BaseController constructor.
     * @param $router Router
     * @param $dbManager DatabaseManager
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    public function process($request, $response, $actionName) {
        //@TODO: Proper action name validation
        $actionMethodName = $actionName . "Action";
        $viewData = $this->$actionMethodName($request, $response);
        if (!is_array($viewData)) {
            return;
        }

        $response->header("Content-Type", "application/json");
        $response->header('Access-Control-Allow-Origin', '*');
        
        $response->end(json_encode($viewData));
        //$viewData = array_merge($viewData, TemplateGlobals::BuildGlobals());
        return;
    }

    public function Redirect($targetUrl) {
        header("Location: ".$targetUrl);
        die();
    }
}