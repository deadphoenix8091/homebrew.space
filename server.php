<?php
require_once './vendor/autoload.php';

$http = new Swoole\HTTP\Server("0.0.0.0", 80);

$loader = new \Twig\Loader\FilesystemLoader('./views');
$twig = new \Twig\Environment($loader, array(
));

$static = [
    'css'  => 'text/css',
    'js'   => 'text/javascript',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'jpg'  => 'image/jpg',
    'jpeg' => 'image/jpg',
    'mp4'  => 'video/mp4',
    'eot' => 'application/vnd.ms-fontobject',
    'svg' => 'image/svg+xml',
    'ttf' => 'application/font-ttf',
    'woff' => 'application/font-woff',
    'woff2' => 'font/woff2'
];

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

$http->on('request', function ($request, $response) use ($twig, $static) {
    if (getStaticFile($request, $response, $static)) {
        return;
    }

    $router = new \HomebrewDB\Router($twig);
    $response->header("Content-Type", "text/html");
    $response->end($router->process($request));
});

$http->start();

function getStaticFile(
    swoole_http_request $request,
    swoole_http_response $response,
    array $static
) : bool {
    $staticFile = __DIR__ . $request->server['request_uri'];
    if (! file_exists($staticFile)) {
        return false;
    }
    $type = pathinfo($staticFile, PATHINFO_EXTENSION);
    if (! isset($static[$type])) {
        return false;
    }
    $response->header('Content-Type', $static[$type]);
    $response->sendfile($staticFile);
    return true;
}
