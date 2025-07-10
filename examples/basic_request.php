<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Llhttp\Parser;
use Llhttp\Events;

// Create HTTP request parser
$parser = new Parser(Parser::TYPE_REQUEST);

// Set up event callbacks
$parser->on(Events::MESSAGE_BEGIN, function () {
    echo "=== Message Begin ===\n";
});

$parser->on(Events::URL, function (string $url) {
    echo "URL: {$url}\n";
});

$parser->on(Events::HEADER_FIELD, function (string $field) {
    echo "Header Field: {$field}\n";
});

$parser->on(Events::HEADER_VALUE, function (string $value) {
    echo "Header Value: {$value}\n";
});

$parser->on(Events::HEADERS_COMPLETE, function () use ($parser) {
    echo "=== Headers Complete ===\n";
    echo "HTTP Version: {$parser->getHttpMajor()}.{$parser->getHttpMinor()}\n";
    echo "Method: {$parser->getMethodName()}\n";
    echo "Headers:\n";
    foreach ($parser->getHeaders() as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                echo "  {$name}: {$v}\n";
            }
        } else {
            echo "  {$name}: {$value}\n";
        }
    }
});

$parser->on(Events::BODY, function (string $body) {
    echo "Body chunk: " . json_encode($body) . "\n";
});

$parser->on(Events::MESSAGE_COMPLETE, function () {
    echo "=== Message Complete ===\n";
});

// Sample HTTP request
$httpRequest = "GET /hello/world?foo=bar HTTP/1.1\r\n" .
               "Host: example.com\r\n" .
               "User-Agent: PHP-FFI-llhttp/1.0\r\n" .
               "Accept: application/json\r\n" .
               "Content-Length: 13\r\n" .
               "\r\n" .
               "Hello, World!";

echo "Parsing HTTP Request:\n";
echo "====================\n";
echo $httpRequest . "\n\n";

echo "Parser Output:\n";
echo "==============\n";

try {
    // Execute parsing
    $parser->execute($httpRequest);
    $parser->finish();
    
    echo "\nParsing completed successfully!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof \Llhttp\Exception) {
        echo "Error Code: " . $e->getLlhttpErrorCode() . "\n";
    }
}