<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class DefinePathMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        defined('ROOT') or define('ROOT', realpath('..') . DS);
        defined('VAR_PATH') or define('VAR_PATH', ROOT . 'var' . DS);

        return $handler->handle($request);
    }
}
