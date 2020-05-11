<?php

namespace Phidias\Api\Log;

// use Phidias\Api\Oauth\Token;
use Phidias\Oauth\Token;

class Log
{
    private static $logFile;

    public static function setLogFile($path)
    {
        self::$logFile = $path;
    }

    public static function save($request, $response, $duration)
    {
        if (!self::$logFile) {
            return;
        }

        $now = time();
        $tokenPayload = Token::getPayload();

        $record = [
            "date" => date("Y-m-d H:i:s O", $now),
            "ip" => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 0),
            "host" => $request->getHeader("host")[0],
            "url" => $request->getUri()->getPath(),
            "method" => $request->getMethod(),
            "args" => $request->getQueryParams(),
            "userId" => isset($tokenPayload->id) ? $tokenPayload->id : null,
            "status" => $response->getStatusCode(),
            "duration" => ceil($duration * 1000)
        ];

        if ($fp = @fopen(self::$logFile, 'a')) {
            fwrite($fp, json_encode($record) . "\n");
            fclose($fp);
        }
    }
}
