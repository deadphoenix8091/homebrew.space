<?php

namespace HomebrewDB\Controllers;

use HomebrewDB\BaseController;

class HomeController extends BaseController {
    protected $viewFolder = 'home';

    public function indexAction() {
        return [];
    }
}