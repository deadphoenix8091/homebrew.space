<?php
require_once './vendor/autoload.php';

$http = new Swoole\HTTP\Server("0.0.0.0", 80);

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
    echo "Swoole http server is started at http://127.0.0.1:80\n";
});

$http->on('request', function ($request, $response) use ($twig) {
    $router = new \HomebrewDB\Router($twig);
    $response->header("Content-Type", "text/html");
    $response->end($router->process());
});

$http->start();