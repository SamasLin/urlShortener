<?php
declare(strict_types=1);

namespace Tests\Application\Actions\Url;

use App\Application\Actions\ActionPayload;
use App\Application\Actions\Url\UrlAction;
use Tests\TestCase;

class ParseUrlActionTest extends TestCase
{
    use SetUrlMapTrait;

    public function testAction()
    {
        $this->initDefinePath();

        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $nowTs = time();

        // empty code
        $url = '/';
        $result = [
            'errorCode' => 2,
            'message'   => 'invalid',
            'data'      => 'empty url code'
        ];
        $mockUrlMap = [];

        $this->setUrlMap($container, $mockUrlMap);

        $request = $this->createRequest('GET', $url);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // empty map
        $url = '/abc';
        $result = [
            'errorCode' => 1,
            'message'   => 'fail',
            'data'      => 'no url data'
        ];
        $mockUrlMap = [];

        $this->setUrlMap($container, $mockUrlMap);

        $request = $this->createRequest('GET', $url);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // success
        $url = '/abc';
        $result = [
            'errorCode' => 0,
            'message'   => 'success',
            'data'      => ['url' => 'http://www.google.com']
        ];
        $mockUrlMap = [
            'abc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];

        $this->setUrlMap($container, $mockUrlMap);

        $request = $this->createRequest('GET', $url);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // unkonwn code
        $url = '/bcd';
        $result = [
            'errorCode' => 1,
            'message'   => 'fail',
            'data'      => 'no url data'
        ];
        $mockUrlMap = [
            'abc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];

        $this->setUrlMap($container, $mockUrlMap);

        $request = $this->createRequest('GET', $url);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // expired code
        $url = '/abc';
        $result = [
            'errorCode' => 1,
            'message'   => 'fail',
            'data'      => 'code is expired'
        ];
        $mockUrlMap = [
            'abc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs - 1
            ]
        ];

        $this->setUrlMap($container, $mockUrlMap);

        $request = $this->createRequest('GET', $url);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
