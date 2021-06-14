<?php

namespace Mamikon\SwoolePsr7\Tests;

use Swoole\Http\Request;

trait SwooleRequestBuilderTrait
{
    private function buildSwooleRequest(
        $requestUri = '/',
        $method = 'GET',
        $postBody = null,
        $pathInfo = '/',
        $requestTime = 1620686600,
        $requestTimeFloat = 1620686600.6553,
        $serverProtocol = 'HTTP/1.1',
        $serverPort = 9501,
        $remotePort = 52456,
        $remoteAddr = '127.0.0.1',
        $masterTime = 1620686600
    ) {
        $swooleRequest = $this->getMockBuilder(Request::class)->getMock();
        $swooleRequest->server = [
            'request_method' => $method,
            'request_uri' => $requestUri,
            'path_info' => $pathInfo,
            'request_time' => $requestTime,
            'request_time_float' => $requestTimeFloat,
            'server_protocol' => $serverProtocol,
            'server_port' => $serverPort,
            'remote_port' => $remotePort,
            'remote_addr' => $remoteAddr,
            'master_time' => $masterTime,
        ];
        $swooleRequest->header = [
            'host' => 'localhost:9501',
        ];
        $swooleRequest->post = $postBody;

        $swooleRequest
            ->expects($this->any())
            ->method('rawContent')
            ->willReturn($this->mockRawContent($swooleRequest));

        return $swooleRequest;
    }

    private function mockRawContent($swooleRequest)
    {
        if (empty($swooleRequest->post)) {
            return null;
        }

        return http_build_query($swooleRequest->post);
    }
}
