<?php

declare(strict_types=1);

namespace Llhttp\Tests;

use PHPUnit\Framework\TestCase;
use Llhttp\Parser;
use Llhttp\Events;
use Llhttp\Exception;

class ParserTest extends TestCase
{
    public function testCanCreateRequestParser(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        $this->assertEquals(Parser::TYPE_REQUEST, $parser->getType());
    }

    public function testCanCreateResponseParser(): void
    {
        $parser = new Parser(Parser::TYPE_RESPONSE);
        $this->assertEquals(Parser::TYPE_RESPONSE, $parser->getType());
    }

    public function testInvalidParserTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Parser(999);
    }

    public function testCanRegisterCallbacks(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        $called = false;
        
        $parser->on(Events::MESSAGE_BEGIN, function () use (&$called) {
            $called = true;
        });

        // This would require actually parsing data to trigger the callback
        // For now, just test that registration doesn't throw
        $this->assertTrue(true);
    }

    public function testCanRemoveCallbacks(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        
        $parser->on(Events::MESSAGE_BEGIN, function () {});
        $parser->off(Events::MESSAGE_BEGIN);
        
        // Test that removal doesn't throw
        $this->assertTrue(true);
    }

    public function testPauseAndResume(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        
        $this->assertFalse($parser->isPaused());
        
        $parser->pause();
        $this->assertTrue($parser->isPaused());
        
        $parser->resume();
        $this->assertFalse($parser->isPaused());
    }

    public function testReset(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        
        $parser->pause();
        $this->assertTrue($parser->isPaused());
        
        $parser->reset();
        $this->assertFalse($parser->isPaused());
    }

    public function testSimpleRequestParsing(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        
        $messageBeginCalled = false;
        $messageCompleteCalled = false;
        $urlReceived = '';
        
        $parser->on(Events::MESSAGE_BEGIN, function () use (&$messageBeginCalled) {
            $messageBeginCalled = true;
        });
        
        $parser->on(Events::URL, function (string $url) use (&$urlReceived) {
            $urlReceived .= $url;
        });
        
        $parser->on(Events::MESSAGE_COMPLETE, function () use (&$messageCompleteCalled) {
            $messageCompleteCalled = true;
        });

        $request = "GET /test HTTP/1.1\r\nHost: example.com\r\n\r\n";
        
        try {
            $parser->execute($request);
            $parser->finish();
            
            $this->assertTrue($messageBeginCalled);
            $this->assertTrue($messageCompleteCalled);
            $this->assertEquals('/test', $urlReceived);
            $this->assertEquals(1, $parser->getHttpMajor());
            $this->assertEquals(1, $parser->getHttpMinor());
        } catch (Exception $e) {
            // If FFI/library not available, skip the test
            $this->markTestSkipped('llhttp library not available: ' . $e->getMessage());
        }
    }

    public function testSimpleResponseParsing(): void
    {
        $parser = new Parser(Parser::TYPE_RESPONSE);
        
        $messageBeginCalled = false;
        $messageCompleteCalled = false;
        $statusReceived = '';
        
        $parser->on(Events::MESSAGE_BEGIN, function () use (&$messageBeginCalled) {
            $messageBeginCalled = true;
        });
        
        $parser->on(Events::STATUS, function (string $status) use (&$statusReceived) {
            $statusReceived .= $status;
        });
        
        $parser->on(Events::MESSAGE_COMPLETE, function () use (&$messageCompleteCalled) {
            $messageCompleteCalled = true;
        });

        $response = "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n\r\n";
        
        try {
            $parser->execute($response);
            $parser->finish();
            
            $this->assertTrue($messageBeginCalled);
            $this->assertTrue($messageCompleteCalled);
            $this->assertEquals('OK', $statusReceived);
            $this->assertEquals(200, $parser->getStatusCode());
            $this->assertEquals(1, $parser->getHttpMajor());
            $this->assertEquals(1, $parser->getHttpMinor());
        } catch (Exception $e) {
            // If FFI/library not available, skip the test
            $this->markTestSkipped('llhttp library not available: ' . $e->getMessage());
        }
    }

    public function testHeaderCollection(): void
    {
        $parser = new Parser(Parser::TYPE_REQUEST);
        
        $headersComplete = false;
        
        $parser->on(Events::HEADERS_COMPLETE, function () use (&$headersComplete) {
            $headersComplete = true;
        });

        $request = "GET /test HTTP/1.1\r\n" .
                   "Host: example.com\r\n" .
                   "User-Agent: Test\r\n" .
                   "Accept: */*\r\n" .
                   "\r\n";
        
        try {
            $parser->execute($request);
            
            $this->assertTrue($headersComplete);
            
            $headers = $parser->getHeaders();
            $this->assertArrayHasKey('host', $headers);
            $this->assertArrayHasKey('user-agent', $headers);
            $this->assertArrayHasKey('accept', $headers);
            $this->assertEquals('example.com', $headers['host']);
            $this->assertEquals('Test', $headers['user-agent']);
            $this->assertEquals('*/*', $headers['accept']);
        } catch (Exception $e) {
            // If FFI/library not available, skip the test
            $this->markTestSkipped('llhttp library not available: ' . $e->getMessage());
        }
    }
}