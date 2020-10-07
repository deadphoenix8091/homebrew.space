<?php

use HomebrewSpace\Controllers\ReleasesCronjobController;
use HomebrewSpace\Controllers\StaticFileController;
use HomebrewSpace\DatabaseManager;

require_once './vendor/autoload.php';

chdir('/code');
$sch = new Swoole\Coroutine\Scheduler();
Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
$sch->set(['hook_flags' => SWOOLE_HOOK_ALL]);

$http = new \Swoole\HTTP\Server("0.0.0.0", 80);

$loader = new \Twig\Loader\FilesystemLoader('./views');
$twig = new \Twig\Environment($loader, array(
));
Swoole\Timer::set(array(
    'enable_coroutine' => TRUE
));

$callbackArray = [];
$updateTimer = function () use (&$callbackArray) {
    try {
        ReleasesCronjobController::run();
    } catch (\Swoole\Error $th) {
        //throw $th;
    }

    foreach($callbackArray as $currentFunc) Swoole\Timer::after(800, $currentFunc);
};
$callbackArray[] = $updateTimer;



Swoole\Timer::after(300, function () use ($updateTimer) {
    DatabaseManager::ImportSeedData(json_decode(file_get_contents('importData.json'), true));

    Swoole\Timer::after(800, $updateTimer);
});

$function_filedate = new \Twig\TwigFunction(
    'fileDate',
    function ($file_path) {
        $change_date = @filemtime($_SERVER['DOCUMENT_ROOT'] . '/' . $file_path);
        if (!$change_date) {
            //Fallback if mtime could not be found:
            $change_date = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        }
        return preg_replace('{\\.([^./]+)$}', ".\$1?" . $change_date, $file_path);
    }
);
$twig->addFunction($function_filedate);

$http->on('start', function ($server) {
    printf("HTTP server started at %s:%s\n", $server->host, $server->port);
    printf("Master  PID: %d\n", $server->master_pid);
    printf("Manager PID: %d\n", $server->manager_pid);


});

$http->on('request', function ($request, $response) use ($twig) {
    if (StaticFileController::handleStaticFile($request, $response)) {
        return;
    }
    echo "Got new request " . PHP_EOL;
    $router = new \HomebrewSpace\Router($twig);
    $response->header("Content-Type", "application/json");
    $response->header('Access-Control-Allow-Origin', '*');
    
    $response->end($router->process($request, $response));
});

$http->start();
