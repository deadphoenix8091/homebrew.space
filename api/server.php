<?php

use HomebrewSpace\Controllers\StaticFileController;
use HomebrewSpace\Models\Application;

require_once './vendor/autoload.php';

chdir('/code');
$sch = new Swoole\Coroutine\Scheduler();
$sch->set(['hook_flags' => SWOOLE_HOOK_ALL]);

$http = new \Swoole\HTTP\Server("0.0.0.0", 80);

$loader = new \Twig\Loader\FilesystemLoader('./views');
$twig = new \Twig\Environment($loader, array(
));

$function_filedate = new \Twig\TwigFunction(
    'fileDate',
    function ($file_path) {
        $change_date = @filemtime($_SERVER['DOCUMENT_ROOT'].'/'.$file_path);
        if (!$change_date) {
            //Fallback if mtime could not be found:
            $change_date = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        }
        return preg_replace('{\\.([^./]+)$}', ".\$1?".$change_date, $file_path);
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

    $router = new \HomebrewSpace\Router($twig);
    $response->header("Content-Type", "application/json");
    $response->end($router->process($request));
});

$http->start();
