<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Llhttp\Parser;
use Llhttp\Events;

// Create HTTP response parser
$parser = new Parser(Parser::TYPE_RESPONSE);

// Set up event callbacks
$parser->on(Events::MESSAGE_BEGIN, function () {
    echo "=== Message Begin ===\n";
});

$parser->on(Events::STATUS, function (string $status) {
    echo "Status: {$status}\n";
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
    echo "Status Code: {$parser->getStatusCode()}\n";
    echo "Keep Alive: " . ($parser->shouldKeepAlive() ? 'Yes' : 'No') . "\n";
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

// Sample HTTP response
$httpResponse = "HTTP/1.1 200 OK\r\n" .
                "Content-Type: application/json\r\n" .
                "Content-Length: 27\r\n" .
                "Server: nginx/1.18.0\r\n" .
                "Connection: keep-alive\r\n" .
                "\r\n" .
                '{"message": "Hello, World!"}';

echo "Parsing HTTP Response:\n";
echo "======================\n";
echo $httpResponse . "\n\n";

echo "Parser Output:\n";
echo "==============\n";

try {
    // Execute parsing
    $parser->execute($httpResponse);
    $parser->finish();
    
    echo "\nParsing completed successfully!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof \Llhttp\Exception) {
        echo "Error Code: " . $e->getLlhttpErrorCode() . "\n";
    }
}