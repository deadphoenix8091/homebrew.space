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
            $viewData = [$viewData];
        }
        //$viewData = array_merge($viewData, TemplateGlobals::BuildGlobals());
        return json_encode($viewData);
    }

    public function Redirect($targetUrl) {
        header("Location: ".$targetUrl);
        die();
    }
}