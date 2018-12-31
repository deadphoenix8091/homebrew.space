<?php

namespace HomebrewDB;


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

    public function process($actionName) {
        //@TODO: Proper action name validation
        $actionMethodName = $actionName . "Action";
        $viewData = $this->$actionMethodName();
        if (!is_array($viewData)) {
            $viewData = [$viewData];
        }
        $viewData = array_merge($viewData, TemplateGlobals::BuildGlobals());
        echo $this->router->twigEnvironment->render($this->viewFolder . '/' . $actionName . '.html', $viewData);
    }

    public function Redirect($targetUrl) {
        header("Location: ".$targetUrl);
        die();
    }
}