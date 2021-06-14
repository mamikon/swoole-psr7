<?php

namespace Mamikon\SwoolePsr7\Tests;

use Mamikon\SwoolePsr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

class RequestTest extends TestCase
{
    use SwooleRequestBuilderTrait;

    public function testProtocolVersion()
    {
        $request = $this->buildSwooleRequest(serverProtocol: 'HTTP/1.2');
        $psrRequest = new Request($request);
        $this->assertEquals('1.2', $psrRequest->getProtocolVersion());
    }

    public function testHeaders()
    {
        $request = $this->buildSwooleRequest(serverProtocol: 'HTTP/1.2');
        $psrRequest = new Request($request);
        $this->assertEquals(['localhost:9501'], $psrRequest->getHeader('host'));
    }

    public function testRequestTarget()
    {
        $uri = '/some-uri?dummy=test';
        $request = $this->buildSwooleRequest(requestUri: $uri);
        $psrRequest = new Request($request);
        $this->assertEquals($uri, $psrRequest->getRequestTarget());
    }

    public function testUri()
    {
        $request = $this->buildSwooleRequest('/foo');
        $queryString = 'foo=1&bar=2';
        $request->server['query_string'] = $queryString;
        $userInfo = 'someuser:somepass';
        $request->header['authorization'] = 'Basic '.base64_encode($userInfo);
        $psrRequest = new Request($request);
        $this->assertInstanceOf(UriInterface::class, $psrRequest->getUri());
        $this->assertEquals('/foo', $psrRequest->getUri()->getPath());
        $this->assertEquals($queryString, $psrRequest->getUri()->getQuery());
        $this->assertEquals($userInfo, $psrRequest->getUri()->getUserInfo());
        $this->assertEquals('localhost', $psrRequest->getUri()->getHost());
        $this->assertEquals(9501, $psrRequest->getUri()->getPort());
    }
}
