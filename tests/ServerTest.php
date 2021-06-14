<?php

namespace Mamikon\SwoolePsr7\Tests;

use Mamikon\SwoolePsr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class ServerTest extends TestCase
{
    use SwooleRequestBuilderTrait;

    public function testServerParams()
    {
        $request = $this->buildSwooleRequest();
        $psrRequest = new ServerRequest($request);
        $this->assertEquals($_SERVER, $psrRequest->getServerParams());
    }

    public function testUploadedFiles()
    {
        $swooleRequest = $this->buildSwooleRequest('/', 'post');
        $filepath = __DIR__.'/dummy.txt';
        $swooleRequest->files = [
            'name1' => [
                'tmp_name' => $filepath,
                'name' => basename('dummy.txt'),
                'type' => 'application/txt',
                'size' => filesize($filepath),
                'error' => 0,
            ],
        ];
        $request = new ServerRequest($swooleRequest);
        $this->assertNotEmpty($request->getUploadedFiles());

        foreach ($request->getUploadedFiles() as $file) {
            $this->assertInstanceOf(UploadedFileInterface::class, $file);
            $this->assertEquals($file->getClientFilename(), 'dummy.txt');
            $this->assertEquals($file->getClientMediaType(), 'application/txt');
            $this->assertEquals($file->getError(), 0);
            $this->assertEquals($file->getSize(), filesize($filepath));
        }
    }

    public function testWithUploadedFiles()
    {
        $swooleRequest = $this->buildSwooleRequest();
        $filepath = __DIR__.'/dummy.txt';
        $swooleRequest->files = [
            $filepath => [
                'size' => filesize($filepath),
                'error' => 0,
                'name' => basename($filepath),
                'type' => 'application/txt',
                'tmp_name' => $filepath,
            ],
        ];

        $request = new ServerRequest($swooleRequest);
        $this->assertNotEmpty($request->getUploadedFiles());

        foreach ($request->getUploadedFiles() as $file) {
            $this->assertInstanceOf(UploadedFileInterface::class, $file);
            $this->assertEquals($file->getClientFilename(), 'dummy.txt');
            $this->assertEquals($file->getClientMediaType(), 'application/txt');
            $this->assertEquals($file->getError(), 0);
            $this->assertEquals($file->getSize(), filesize($filepath));
        }
    }

    /**
     * @test
     */
    public function getParsedBodyNull()
    {
        $swooleRequest = $this->buildSwooleRequest('/', 'post');
        $swooleRequest->post = [];
        $request = new ServerRequest($swooleRequest);
        $this->assertTrue(is_null($request->getParsedBody()));
    }

    /**
     * @test
     */
    public function getParsedBody()
    {
        $swooleRequest = $this->buildSwooleRequest('/', 'post');
        $swooleRequest->post = [
            'test' => 1,
        ];
        $request = new ServerRequest($swooleRequest);
        $this->assertEquals($swooleRequest->post, $request->getParsedBody());
    }

    /**
     * @test
     */
    public function withParsedBody()
    {
        $swooleRequest = $this->buildSwooleRequest('/', 'post');
        $swooleRequest->post = [
            'test' => 1,
        ];
        $newPost = ['test' => 2];
        $request = new ServerRequest($swooleRequest);
        $new = $request->withParsedBody($newPost);
        $this->assertEquals($newPost, $new->getParsedBody());
    }
}
