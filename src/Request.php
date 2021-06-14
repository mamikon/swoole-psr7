<?php

namespace Mamikon\SwoolePsr7;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\MessageTrait;
use Nyholm\Psr7\RequestTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    public function __construct(protected \Swoole\Http\Request $swooleRequest, protected UriFactoryInterface | null $uriFactory = null, protected StreamFactoryInterface | null $streamFactory = null)
    {
        $this->setProtocol();
        $this->setHeaders($this->swooleRequest->header);
        $this->method = $swooleRequest->server['request_method'] ?? 'get';
        $this->bindUri();
        $this->bindStream();
    }

    private function setProtocol()
    {
        $protocol = explode('/', $this->swooleRequest->server['server_protocol']);
        $this->protocol = $protocol[1] ?? '1.1';
    }

    private function bindUri(): void
    {
        $queryString = !empty($this->swooleRequest->server['query_string'])
            ? '?'.$this->swooleRequest->server['query_string']
            : '';
        $requestTarget = $this->swooleRequest->server['request_uri']
            .$queryString;

        $userInfo = $this->parseUserInfo() ?? null;

        $uri = (!empty($userInfo) ? '//'.$userInfo.'@' : '')
            .$this->swooleRequest->header['host']
            .$requestTarget;
        if (!$this->uriFactory) {
            $this->uriFactory = new Psr17Factory();
        }
        $this->uri = $this->uriFactory->createUri($uri);
    }

    private function parseUserInfo(): bool | string | null
    {
        $authorization = $this->swooleRequest->header['authorization'] ?? '';
        if (str_starts_with($authorization, 'Basic')) {
            $parts = explode(' ', $authorization);

            return base64_decode($parts[1]);
        }

        return null;
    }

    private function bindStream()
    {
        if (!$this->streamFactory) {
            $this->streamFactory = new Psr17Factory();
        }
        $this->streamFactory->createStream($this->swooleRequest->rawContent() ?? '');
    }
}
