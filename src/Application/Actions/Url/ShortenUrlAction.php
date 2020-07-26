<?php
declare(strict_types=1);

namespace App\Application\Actions\Url;

use Psr\Http\Message\ResponseInterface as Response;

class ShortenUrlAction extends UrlAction
{
    const URL_SLOT_MAX = 10000;
    const CODE_LEN_MIN = 3;
    const CODE_LEN_MAX = 10;
    const MAP_LOCK_FILE = VAR_PATH . 'lock';

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $params = $this->request->getParsedBody();
        if (empty($params) || !is_array($params) || !isset($params['url'])) {
            return $this->invalid('input format is error');
        }

        $originalUrl = $params['url'];
        if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
            return $this->invalid('not valid url');
        }

        $nowTs = time();

        $fileLock = fopen(self::MAP_LOCK_FILE, 'r');
        $retryLock = 5;
        do {
            $lock = flock($fileLock, LOCK_EX);
            if (!$lock) {
                usleep(300);
            }
        } while (!$lock && $retryLock-- > 0);
        if (!$lock) {
            flock($fileLock, LOCK_UN);
            fclose($fileLock);
            return $this->fail('system is busy, try later');
        }
        
        $urlMap = $this->getUrlMap();

        $duplicated = array_filter($urlMap, function ($raw) use ($originalUrl, $nowTs) {
            return $raw['url'] == $originalUrl;// && $raw['expireTs'] >= $nowTs;
        });
        if (!empty($duplicated)) {
            $result = [
                'code' => array_keys($duplicated)[0],
                'sn' => array_values($duplicated)[0]['sn']
            ];
        } else {
            $result = $this->getCodeInfo($urlMap, $nowTs);
            if ($result === false) {
                flock($fileLock, LOCK_UN);
                fclose($fileLock);
                return $this->fail('too many url in service');
            }
        }

        $urlMap[$result['code']] = [
            'sn' => $result['sn'],
            'url' => $originalUrl,
            'expireTs' => $nowTs + parent::EXPIRE_PERIOD
        ];
        $update = $this->updateMap($urlMap);
        flock($fileLock, LOCK_UN);
        fclose($fileLock);

        return $update === false ? $this->fail('fail, try later') : $this->success(['code' => $result['code']]);
    }

    protected function getCodeInfo(array $urlMap, int $nowTs, int $slotMax = self::URL_SLOT_MAX)
    {
        if (empty($urlMap)) {
            return [
                'sn' => 0,
                'code' => $this->genCode($urlMap, 0)
            ];
        }

        $maxSN = max(array_column($urlMap, 'sn'));
        for ($newSN = $maxSN + 1; $newSN < $slotMax; $newSN++) {
            $code = $this->genCode($urlMap, $newSN);
            if ($code !== false) {
                return [
                    'sn' => $newSN,
                    'code' => $code
                ];
            }
        }

        $usableSlot = array_filter($urlMap, function ($raw) use ($nowTs) {
            return $raw['expireTs'] <= $nowTs;
        });

        if (count($usableSlot) === 0) {
            return false;
        }

        $expireMap = array_combine(array_keys($usableSlot), array_column($usableSlot, 'expireTs'));
        $code = array_search(min($expireMap), $expireMap);
        return [
            'sn' => $usableSlot[$code]['sn'],
            'code' => $code
        ];
    }

    protected function genCode(
        array $urlMap,
        int $sn,
        int $codeMinLength = self::CODE_LEN_MIN,
        int $codeMaxLength = self::CODE_LEN_MAX
    ) {
        $hash = md5((string)$sn);
        $code = substr($hash, 0, $codeMinLength - 1);
        $paddingList = str_split(substr($hash, $codeMinLength - 1, $codeMaxLength - $codeMinLength));

        foreach ($paddingList as $char) {
            $code .= $char;
            if (!isset($urlMap[$code])) {
                return $code;
            }
        }

        return false;
    }

    protected function updateMap(array $urlMap) {
        $pathInfo = pathinfo(parent::MAP_FILE);
        if (!file_exists($pathInfo['dirname'])) {
            mkdir($pathInfo['dirname'], 0700, true);
        }

        return file_put_contents(parent::MAP_FILE, json_encode($urlMap));
    }
}
