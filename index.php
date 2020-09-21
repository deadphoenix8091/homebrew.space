<?php
require_once './vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('./views');
$twig = new Twig_Environment($loader);

\HomebrewSpace\SessionManager::Start();
$router = new \HomebrewSpace\Router($twig);
$router->process();
