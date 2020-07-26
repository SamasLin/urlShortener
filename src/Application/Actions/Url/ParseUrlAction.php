<?php
declare(strict_types=1);

namespace App\Application\Actions\Url;

use Psr\Http\Message\ResponseInterface as Response;

class ParseUrlAction extends UrlAction
{
    protected function action(): Response
    {
        $code = $this->resolveArg('code');

        if ($code === '') {
            return $this->invalid('empty url code');
        }

        $urlMap = $this->getUrlMap();
        if (!isset($urlMap[$code])) {
            return $this->fail('no url data');
        } elseif ($urlMap[$code]['expireTs'] < time()) {
            return $this->fail('code is expired');
        }
        return $this->success(['url' => $urlMap[$code]['url']]);
    }
}
