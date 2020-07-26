<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use App\Application\Actions\Url\ShortenUrlAction;
use App\Application\Actions\Url\ParseUrlAction;
use App\Application\Middleware\DefinePathMiddleware;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        $basicHeaders = [
            'Origin',
            'Content-Type',
            'X-Lang'
        ];
        $authHeaders = [
            // add customized header here
        ];
        $allowHeaders = array_unique(
            array_merge(
                $basicHeaders,
                $hashHeaders,
                $storeApiHeaders,
                $adminHeaders
            )
        );
        $response->withHeader('Access-Control-Allow-Origin', '*')
                 ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                 ->withHeader('Access-Control-Allow-Headers', implode(',', $allowHeaders))
                 ->withHeader('Access-Control-Max-Age', 86400);
        return $response;
    });

    $definePathMiddleware = new DefinePathMiddleware;

    $app->post('/', ShortenUrlAction::class)->add($definePathMiddleware);
    $app->get('/{code:.*}', ParseUrlAction::class)->add($definePathMiddleware);
};
