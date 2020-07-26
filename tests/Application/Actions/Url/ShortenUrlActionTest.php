<?php
declare(strict_types=1);

namespace Tests\Application\Actions\Url;

use ReflectionMethod;
use Psr\Log\LoggerInterface;
use App\Application\Actions\ActionPayload;
use App\Application\Actions\Url\ShortenUrlAction;
use App\Application\Actions\Url\UrlAction;
use App\Application\Middleware\DefinePathMiddleware;
use Tests\TestCase;

class ShortenUrlActionTest extends TestCase
{
    use SetUrlMapTrait;

    public function testAction()
    {
        $this->initDefinePath();

        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $backupUrlMap = file_get_contents(VAR_PATH . 'map/map.json');

        // wrong format
        $formData = [];
        $urlMap = [];
        $result = [
            'errorCode' => 2,
            'message'   => 'invalid',
            'data'      => 'input format is error'
        ];

        $this->setUrlMap($container, $urlMap);

        $request = $this->createRequest('POST', '/')->withParsedBody($formData);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // invalid url
        $formData = ['url' => 'abc123'];
        $urlMap = [];
        $result = [
            'errorCode' => 2,
            'message'   => 'invalid',
            'data'      => 'not valid url'
        ];

        $this->setUrlMap($container, $urlMap);

        $request = $this->createRequest('POST', '/')->withParsedBody($formData);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // success
        $formData = ['url' => 'http://www.google.com'];
        $urlMap = [];
        $result = [
            'errorCode' => 0,
            'message'   => 'success',
            'data'      => ['code' => 'cfc']
        ];

        $this->setUrlMap($container, $urlMap);

        $request = $this->createRequest('POST', '/')->withParsedBody($formData);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // duplicated url
        $formData = ['url' => 'http://www.google.com'];
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];
        $result = [
            'errorCode' => 0,
            'message'   => 'success',
            'data'      => ['code' => 'cfc']
        ];

        $this->setUrlMap($container, $urlMap);

        $request = $this->createRequest('POST', '/')->withParsedBody($formData);
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $result);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);

        // no slot to use
        // TODO

        // race condition
        // TODO

        // update fail
        // TODO

        file_put_contents(VAR_PATH . 'map/map.json', $backupUrlMap);
    }

    public function testGetCodeInfo()
    {
        $this->initDefinePath();

        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $logger = $container->get(LoggerInterface::class);

        $method = new ReflectionMethod(ShortenUrlAction::class, 'getCodeInfo');
        $method->setAccessible(true);

        $genCode = new ReflectionMethod(ShortenUrlAction::class, 'genCode');
        $genCode->setAccessible(true);

        $nowTs = time();

        // init
        $urlMap = [];
        $ts = $nowTs;
        $slotMax = 100;
        $result = [
            'sn' => 0,
            'code' => $genCode->invoke(new ShortenUrlAction($logger), [], 0)
        ];

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $ts, $slotMax);

        $this->assertEquals($returned, $result);

        // add new
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];
        $ts = $nowTs;
        $slotMax = 100;
        $result = [
            'sn' => 1,
            'code' => $genCode->invoke(
                new ShortenUrlAction($logger),
                [
                    'cfc' => [
                        'sn' => 1,
                        'url' => 'http://www.facebook.com',
                        'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
                    ]
                ],
                1
            )
        ];

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $ts, $slotMax);

        $this->assertEquals($returned, $result);

        // full without expired slot
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];
        $ts = $nowTs;
        $slotMax = 1;
        $result = false;

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $ts, $slotMax);

        $this->assertEquals($returned, $result);

        // full with expired slot
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs - 1
            ]
        ];
        $ts = $nowTs;
        $slotMax = 1;
        $result = [
            'sn' => 0,
            'code' => 'cfc'
        ];

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $ts, $slotMax);

        $this->assertEquals($returned, $result);
    }

    public function testGenCode()
    {
        $this->initDefinePath();

        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        $logger = $container->get(LoggerInterface::class);

        $method = new ReflectionMethod(ShortenUrlAction::class, 'genCode');
        $method->setAccessible(true);

        $nowTs = time();

        // init
        $urlMap = [];
        $sn = 0;
        $codeMinLength = 3;
        $codeMaxLength = 10;
        $result = 'cfc';

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $sn, $codeMinLength, $codeMaxLength);

        $this->assertEquals($returned, $result);

        // add new
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];
        $sn = 1;
        $codeMinLength = 3;
        $codeMaxLength = 10;
        $result = 'c4c';

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $sn, $codeMinLength, $codeMaxLength);

        $this->assertEquals($returned, $result);

        // code conflict
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ]
        ];
        $sn = 0;
        $codeMinLength = 3;
        $codeMaxLength = 10;
        $result = 'cfcd';

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $sn, $codeMinLength, $codeMaxLength);

        $this->assertEquals($returned, $result);

        // code conflict but all code is fully used
        $urlMap = [
            'cfc' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ],
            'cfcd' => [
                'sn' => 0,
                'url' => 'http://www.google.com',
                'expireTs' => $nowTs + UrlAction::EXPIRE_PERIOD
            ],
        ];
        $sn = 0;
        $codeMinLength = 3;
        $codeMaxLength = 4;
        $result = false;

        $returned = $method->invoke(new ShortenUrlAction($logger), $urlMap, $sn, $codeMinLength, $codeMaxLength);

        $this->assertEquals($returned, $result);
    }
}
