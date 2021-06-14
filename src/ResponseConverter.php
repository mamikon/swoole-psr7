<?php

namespace Mamikon\SwoolePsr7;

use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

class ResponseConverter
{
    public const FSTAT_MODE_S_IFIFO = 0010000;

    public function __construct(private ResponseInterface $psrResponse, private Response $swooleResponse)
    {
    }

    public function convert(): Response
    {
        $this->copyHeaders($this->psrResponse, $this->swooleResponse);
        $this->copyBody($this->psrResponse, $this->swooleResponse);
        $this->swooleResponse->status($this->psrResponse->getStatusCode());

        return $this->swooleResponse;
    }

    private function copyHeaders($psrResponse, $swooleResponse)
    {
        if (empty($psrResponse->getHeaders())) {
            return;
        }

        $this->setCookies($swooleResponse, $psrResponse);
        $psrResponse = $psrResponse->withoutHeader('Set-Cookie');
        foreach ($psrResponse->getHeaders() as $key => $headerArray) {
            $swooleResponse->header($key, implode('; ', $headerArray));
        }
    }

    private function setCookies($swooleResponse, $psrResponse)
    {
        if (!$psrResponse->hasHeader('Set-Cookie')) {
            return;
        }

        $setCookies = SetCookies::fromSetCookieStrings($psrResponse->getHeader('Set-Cookie'));
        foreach ($setCookies->getAll() as $setCookie) {
            $swooleResponse->cookie(
                $setCookie->getName(),
                $setCookie->getValue(),
                $setCookie->getExpires(),
                $setCookie->getPath(),
                $setCookie->getDomain(),
                $setCookie->getSecure(),
                $setCookie->getHttpOnly()
            );
        }
    }

    private function copyBody($psrResponse, $swooleResponse)
    {
        if (0 == $psrResponse->getBody()->getSize()) {
            $this->copyBodyIfIsAPipe($psrResponse, $swooleResponse);

            return;
        }
        if ($psrResponse->getBody()->isSeekable()) {
            $psrResponse->getBody()->rewind();
        }
        $swooleResponse->write($psrResponse->getBody()->getContents());
    }

    private function copyBodyIfIsAPipe($psrResponse, $swooleResponse)
    {
        $resource = $psrResponse->getBody()->detach();
        if (!is_resource($resource)) {
            return;
        }
        if ($this->isPipe($resource)) {
            while (!feof($resource)) {
                $buff = fread($resource, 8192);
                !empty($buff) && $swooleResponse->write($buff);
            }
            pclose($resource);
        }
    }

    private function isPipe($resource): bool
    {
        $stat = fstat($resource);

        return isset($stat['mode']) && ($stat['mode'] & self::FSTAT_MODE_S_IFIFO) !== 0;
    }
}
