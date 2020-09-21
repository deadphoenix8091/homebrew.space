<?php

namespace HomebrewSpace;

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

    private function getController($request) {
        $page = $request->server['request_uri'];
        $page = array_filter(explode('?', $page))[0];
        $targetRoute = ['Home', 'index'];

        $urlSegments = array_values(array_filter(explode('/', $page)));

        if (count($urlSegments) > 0) {
            switch ($urlSegments[0]) {
                case 'submit':
                    $targetRoute = ['Submission', 'form'];
                    break;
                case 'releases5551':
                    $targetRoute = ['ReleasesCronjob', 'index'];
                    break;
                case 'category':
                    $targetRoute = ['Home', 'category'];
                    break;
                case 'search':
                    $targetRoute = ['Home', 'search'];
                    break;
                case 'dl':
                    $targetRoute = ['Download', 'index'];
                    break;
                case 'qr':
                    $targetRoute = ['QR', 'index'];
                    break;
                case 'credits':
                    $targetRoute = ['Home', 'credits'];
                    break;
                case 'rules-guidelines':
                    $targetRoute = ['Home', 'rules'];
                    break;
                case 'api':
                    if (count($urlSegments) > 1) {
                        switch ($urlSegments[1]) {
                            case 'apps':
                                $targetRoute = ['API', 'apps'];
                                break;
                            case 'categories':
                                $targetRoute = ['API', 'categories'];
                                break;
                            case 'search':
                                $targetRoute = ['API', 'search'];
                                break;
                            default:
                                $targetRoute = ['Home', 'index'];
                                break;
                        }
                    } else {
                        $targetRoute = ['Home', 'index'];
                        break;
                    }
                    break;
                default:
                    $targetRoute = ['Home', 'index'];
                    break;
            }
        }

        return $targetRoute;
    }

    /**
     * @param $dbManager DatabaseManager
     */
    public function process($request) {
        $controllerAction = $this->getController($request);

        $controllerClassName = "\\HomebrewSpace\\Controllers\\" . $controllerAction[0] . "Controller";
        /** @var BaseController $controllerInstance */
        $controllerInstance = new $controllerClassName($this);
        return $controllerInstance->process($controllerAction[1]); //@TODO: Pass Request info somehow
    }
}
