<?php
require_once './vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('./views');
$twig = new Twig_Environment($loader);

\HomebrewDB\SessionManager::Start();
$router = new \HomebrewDB\Router($twig);
$router->process();
