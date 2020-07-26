<?php
declare(strict_types=1);

namespace Tests\Application\Actions\Url;

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Application\Actions\Url\ParseUrlAction;
use App\Application\Actions\Url\ShortenUrlAction;

trait SetUrlMapTrait
{
    protected function initDefinePath()
    {
        defined('DS') or define('DS', DIRECTORY_SEPARATOR);
        defined('ROOT') or define('ROOT', __DIR__ . '/../../../..' . DS);
        defined('VAR_PATH') or define('VAR_PATH', ROOT . 'var' . DS);
    }

    protected function setUrlMap(Container $container, array $content)
    {
        $logger = $container->get(LoggerInterface::class);

        $mockParseUrlAction = new class($logger, $content) extends ParseUrlAction {
            private $tempUrlMap;

            public function __construct(LoggerInterface $logger, array $map)
            {
                parent::__construct($logger);
                $this->tempUrlMap = $map;
            }

            protected function getUrlMap(): array
            {
                return $this->tempUrlMap;
            }
        };

        $container->set(ParseUrlAction::class, $mockParseUrlAction);

        $mockShortenUrlAction = new class($logger, $content) extends ShortenUrlAction {
            private $tempUrlMap;

            public function __construct(LoggerInterface $logger, array $map)
            {
                parent::__construct($logger);
                $this->tempUrlMap = $map;
            }

            protected function getUrlMap(): array
            {
                return $this->tempUrlMap;
            }
        };

        $container->set(ShortenUrlAction::class, $mockShortenUrlAction);
    }
}
