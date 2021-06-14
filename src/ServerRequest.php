<?php

namespace Mamikon\SwoolePsr7;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    use ServerRequestTrait;

    public function __construct(
        public \Swoole\Http\Request $swooleRequest,
        protected UriFactoryInterface | null $uriFactory = null,
        protected StreamFactoryInterface | null $streamFactory = null,
        private UploadedFileFactoryInterface | null $uploadedFileFactory = null
    ) {
        parent::__construct($swooleRequest, $uriFactory, $streamFactory);
        $this->serverParams = $_SERVER ?? [];
        $this->bindFiles();
        if (!empty($this->swooleRequest->post)) {
            $this->parsedBody = $this->swooleRequest->post;
        }
        if (!empty($this->swooleRequest->get)) {
            $this->queryParams = $this->swooleRequest->get;
        }
    }

    private function bindFiles()
    {
        if (!empty($this->swooleRequest->files)) {
            if (!$this->uploadedFileFactory) {
                $this->uploadedFileFactory = new Psr17Factory();
            }
            foreach ($this->swooleRequest->files as $name => $fileData) {
                if (!empty($fileData['tmp_name']) &&
                    isset($fileData['size']) &&
                    isset($fileData['error']) &&
                    !empty($fileData['name']) &&
                    !empty($fileData['type'])
                ) {
                    $this->uploadedFiles[$name] = $this->uploadedFileFactory->createUploadedFile(
                        $this->streamFactory->createStreamFromFile($fileData['tmp_name']),
                        $fileData['size'],
                        $fileData['error'],
                        $fileData['name'],
                        $fileData['type']
                    );
                }
            }
        }
    }
}
