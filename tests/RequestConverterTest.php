<?php

namespace Mamikon\SwoolePsr7\Tests;

use Datetime;
use Mamikon\SwoolePsr7\ResponseConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Swoole\Http\Response;

class RequestConverterTest extends TestCase
{
    private MockObject | Response $swooleResponse;
    private MockObject | StreamInterface $body;
    private ResponseInterface | MockObject $psrResponse;
    private ResponseConverter $responseMerger;

    public function setUp(): void
    {
        parent::setUp();
        $this->swooleResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->body = $this->getMockForAbstractClass(StreamInterface::class);
        $this->psrResponse = $this->getMockBuilder(ResponseInterface::class)->getMockForAbstractClass();
        $this->psrResponse->expects($this->any())->method('getBody')->willReturn($this->body);
        $this->responseMerger = new ResponseConverter($this->psrResponse, $this->swooleResponse);
    }

    /**
     * @test
     */
    public function returnsSwooleResponse()
    {
        $swooleResponse = $this->responseMerger->convert();
        $this->assertInstanceOf(Response::class, $swooleResponse);
    }

    /**
     * @test
     */
    public function headersGetCopied()
    {
        $this->psrResponse->expects($this->any())->method('getHeaders')->willReturn([
                                                                                        'foo' => ['bar'],
                                                                                        'fiz' => ['bam'],
                                                                                    ]);
        $this->psrResponse->method('withoutHeader')->willReturn($this->psrResponse);

        $this->swooleResponse->expects($headerSpy = $this->exactly(2))->method('header');

        $this->responseMerger->convert();
        $this->assertSame(2, $headerSpy->getInvocationCount());
    }

    /**
     * @test
     */
    public function cookiesShouldBeMergedWithCookieMethod()
    {
        $psrResponseWithoutCookies = clone $this->psrResponse;
        $psrResponseWithoutCookies->method('getHeaders')->willReturn([]);
        $this->psrResponse->method('withoutHeader')->willReturn($psrResponseWithoutCookies);
        $expires = new Datetime('+2 hours');

        $cookieArray = [
            'Cookie1=Value1; Domain=some-domain; Path=/; Expires='
            .$expires->format('l, d-M-Y H:i:s T').' GMT; Secure; HttpOnly',
        ];

        $this->psrResponse->expects($this->any())->method('getHeaders')->willReturn([
                                                                                        'Set-Cookie' => $cookieArray,
                                                                                    ]);
        $this->psrResponse->method('getHeader')->willReturn($cookieArray);
        $this->psrResponse->method('hasHeader')->willReturn(true);
        $this->swooleResponse->expects($headerSpy = $this->exactly(0))->method('header');
        $this->swooleResponse->expects($cookieSpy = $this->exactly(1))->method('cookie')
            ->with('Cookie1', 'Value1', $expires->getTimestamp(), '/', 'some-domain', true, true);

        $this->responseMerger->convert();

        $this->assertSame(0, $headerSpy->getInvocationCount());
        $this->assertSame(1, $cookieSpy->getInvocationCount());
    }

    /**
     * @test
     */
    public function bodyContentGetsCopiedIfNotEmpty()
    {
        $this->body->expects($this->once())->method('getSize')->willReturn(3);
        $this->body->expects($this->once())->method('isSeekable')->willReturn(true);
        $this->body->expects($rewindSpy = $this->once())->method('rewind')->willReturn(null);
        $this->body->expects($this->once())->method('rewind')->willReturn(null);
        $this->body->expects($this->once())->method('getContents')->willReturn('abc');
        $this->swooleResponse->expects($writeSpy = $this->once())->method('write')->with('abc');

        $this->responseMerger->convert();

        $this->assertSame(1, $rewindSpy->getInvocationCount());
        $this->assertSame(1, $writeSpy->getInvocationCount());
    }

    /**
     * @test
     */
    public function bodyContentGetsWrittenIfItIsAPipe()
    {
        $this->body->expects($this->any())->method('getSize')->willReturn(0);

        // named pipe (http://www.manpagez.com/man/2/stat/)
        $namedPipe = ['mode' => 4480];
        $this->body->expects($this->any())->method('getMetadata')->willReturn($namedPipe);

        $this->body->expects($this->any())->method('detach')->willReturn(
            popen('php -r "echo str_repeat(\'x\', 10000);"', 'r')
        );

        $this->swooleResponse->expects($writeSpy = $this->atLeastOnce())->method('write');

        $this->responseMerger->convert();
    }

    /**
     * @test
     */
    public function statusCodeGetsCopied()
    {
        $this->psrResponse->expects($this->once())->method('getStatusCode')->willReturn(400);
        $this->swooleResponse->expects($setStatusSpy = $this->once())->method('status')->with(400);

        $this->responseMerger->convert();

        $this->assertSame(1, $setStatusSpy->getInvocationCount());
    }
}
