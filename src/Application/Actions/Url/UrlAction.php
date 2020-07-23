<?php
declare(strict_types=1);

namespace App\Application\Actions\Url;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;

abstract class UrlAction extends Action
{
    const EXPIRE_PERIOD = 86400 * 30;
    const MAP_FILE = VAR_PATH . 'map/map.json';

    protected function getUrlMap()
    {
        return file_exists(self::MAP_FILE) ? json_decode(file_get_contents(self::MAP_FILE, true), true) : [];
    }
}
