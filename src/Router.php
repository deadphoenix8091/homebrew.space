<?php

namespace HomebrewDB;

class Router {
    /** @var \Twig_Environment */
    public $twigEnvironment;

    /**
     * Router constructor.
     * @param $twigEnvironment \Twig_Environment
     */
    public function __construct($twigEnvironment)
    {
        $this->twigEnvironment = $twigEnvironment;
    }

    private function getController() {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $targetRoute = ['Home', 'index'];

        $urlSegments = explode('/', $page);

        if (count($urlSegments) > 0) {
            switch ($urlSegments[0]) {
                case 'submit':
                    $targetRoute = ['Submission', 'form'];
                    break;
                case 'releases':
                    $targetRoute = ['ReleasesCronjob', 'index'];
                    break;
                case 'tips':
                    if (SessionManager::IsLoggedin()) {
                        $targetRoute = ['Tips', 'index'];
                        break;
                    }
            }
        }

        return $targetRoute;
    }

    /**
     * @param $dbManager DatabaseManager
     */
    public function process() {
        $controllerAction = $this->getController();

        $controllerClassName = "\\HomebrewDB\\Controllers\\" . $controllerAction[0] . "Controller";
        /** @var BaseController $controllerInstance */
        $controllerInstance = new $controllerClassName($this);
        $controllerInstance->process($controllerAction[1]); //@TODO: Pass Request info somehow
    }
}