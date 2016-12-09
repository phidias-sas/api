<?php
namespace Phidias\Api\Environment;

use Phidias\Api\Http\ServerRequest;
use Phidias\Api\Http\Response;
use Phidias\Api\Http\Uri;
use Phidias\Api\Http\Stream;
use Phidias\Api\Http\UploadedFile;

class Apache implements EnvironmentInterface
{
    public static function getServerRequest()
    {
        $request = (new ServerRequest)
            ->withMethod(self::getMethod())
            ->withUri(self::getUri())
            ->withQueryParams(self::getQueryParams())
            ->withBody(self::getBodyStream())
            ->withUploadedFiles(self::getUploadedFiles());

        foreach (self::getHeaders() as $headerName => $headerValue) {
            $request = $request->withHeader($headerName, explode(",", $headerValue));
        }

        return $request;
    }

    public static function sendResponse(Response $response)
    {
        $statusCode      = $response->getStatusCode();
        $reasonPhrase    = $response->getReasonPhrase();
        $protocolVersion = $response->getProtocolVersion();

        $GLOBALS["http_response_code"] = $statusCode;
        header("HTTP/{$protocolVersion} {$statusCode} {$reasonPhrase}");

        foreach ($response->getHeaders() as $headerName => $headerValues) {
            header($headerName . ": " . implode(", ", $headerValues));
        }

        echo (string)$response->getBody();
    }

    private static function getMethod()
    {
        $method = filter_input(INPUT_SERVER, "REQUEST_METHOD");
        return $method !== null ? strtolower($method) : "get";
    }

    private static function getUri()
    {
        $uri = (new Uri())
            ->withScheme(filter_input(INPUT_SERVER, "REQUEST_SCHEME"))
            ->withHost(filter_input(INPUT_SERVER, "HTTP_HOST"))
            ->withPath(filter_input(INPUT_SERVER, "PATH_INFO") ?: '/')
            ->withQuery(filter_input(INPUT_SERVER, "QUERY_STRING") ?: '');

        return $uri;
    }

    private static function getQueryParams()
    {
        return isset($_GET) ? $_GET : [];
    }

    private static function getHeaders()
    {
        return getallheaders();
    }

    private static function getBodyStream()
    {
        $rawInput   = fopen('php://input', 'r');
        $tempStream = fopen('php://temp', 'r+');
        stream_copy_to_stream($rawInput, $tempStream);
        rewind($tempStream);

        return new Stream($tempStream);
    }

    // THIS FUNCTION IS DEPRECATED AND REMAINS HERE ONLY TO HIGHLIGHT THE WEIRD BUG SHOWN INSIDE
    private static function parseBody()
    {
        if (isset($_POST) && !empty($_POST)) {
            return $_POST;
        }

        $inputString = trim(file_get_contents('php://input'));
        if (!$inputString) {
            return null;
        }

        $inputJson = json_decode($inputString);

        /* Well, this is a weird bug.
        In PHP 5.5.3, json_decode("7:1400510592:2aa8ea5870aa55e417f798f75b752902") returns the INTEGER "7"
        */
        if (gettype($inputJson) === 'integer' && !is_numeric($inputString)) {
            $inputJson = null;
        }

        return $inputJson !== null ? $inputJson : $inputString;
    }

    private static function getUploadedFiles()
    {
        if (!isset($_FILES) || empty($_FILES)) {
            return [];
        }

        $retval = [];

        foreach ($_FILES as $file) {
            $retval[] = new UploadedFile(
                $file['tmp_name'],
                $file['size'],
                $file['error'],
                $file['name'],
                $file['type']
            );
        }

        return $retval;
    }
}