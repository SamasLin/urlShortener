<?php
declare(strict_types=1);

namespace App\Application\Actions;

class ErrorCode
{
    private static $errorCodeMap = [
        'unknown'   => -1,
        'success'   => 0,
        'fail'      => 1,
        'invalid'   => 2,
        'exception' => 999
    ];

    /**
     * get error code by error type string
     * @param  string  $type  error type
     * @return int
     */
    public static function getCode(string $type): int
    {
        if (isset(self::$errorCodeMap[$type])) {
            return self::$errorCodeMap[$type];
        }
        return -1;
    }
}