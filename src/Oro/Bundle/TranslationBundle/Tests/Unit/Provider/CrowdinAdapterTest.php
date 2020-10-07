<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class CrowdinAdapterTest extends TestCase
{
    use TempDirExtension;

    /** @var CrowdinAdapter */
    protected $adapter;

    /** @var MockObject|LoggerInterface */
    protected $logger;

    /** @var MockObject|ClientInterface */
    protected $client;

    /** @var MockObject|Response */
    protected $response;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = $this->createMock(ClientInterface::class);
        $this->response = $this->createMock(Response::class);

        $this->adapter = new CrowdinAdapter($this->client);
        $this->adapter->setApiKey('some-api-key');
        $this->adapter->setLogger($this->logger);
    }

    protected function tearDown(): void
    {
        unset($this->logger, $this->client, $this->adapter);
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';
        $separator = DIRECTORY_SEPARATOR;
        $files = [
            "some{$separator}path{$separator}to{$separator}file.yml" => "api{$separator}path{$separator}test.yml",
        ];

        $this->adapter->setProjectId(1);

        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->atLeastOnce())
            ->method('getContents')
            ->willReturn('{"success": true}');
        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        $this->client->expects($this->exactly(4))
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->adapter->upload($files, $mode);
    }

    public function testUploadError()
    {
        $this->expectException(\Exception::class);
        $mode = 'add';
        $files = [
            'some/path/to/file.yml' => 'api/path/test.yml',
        ];

        $this->adapter->setProjectId(1);

        $this->client->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('test')));
        $this->adapter->upload($files, $mode);
    }

    /**
     * Test logging exception
     */
    public function testCreateDirError()
    {
        $dirs = ['some/path'];

        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->atLeastOnce())
            ->method('getContents')
            ->willReturn('{"success": true}');
        $this->response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $this->logger->expects($this->atLeastOnce())
            ->method('info');

        $this->adapter->createDirectories($dirs);
    }

    /**
     * Test logging exception while uploading file
     */
    public function testUploadFiles()
    {
        $files = [
            'some/path/to/file.yml' => 'api/path/test.yml',
        ];

        $this->client->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('test')));
        $this->logger->expects($this->once())
            ->method('error');

        $this->adapter->uploadFiles($files, 'add');
    }

    public function testDownload()
    {
        $locale = 'en';
        $path = $this->getTempDir('trans', false) . DIRECTORY_SEPARATOR . 'target.zip';

        $this->client->expects($this->once())
            ->method('send')
            ->will($this->returnValue($this->response));
        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));


        $res = $this->adapter->download($path, [$locale]);
        $this->assertTrue($res);
    }

    /**
     * test upload empty files array
     */
    public function testUploadEmpty()
    {
        $this->assertFalse($this->adapter->upload([]));
    }
}
