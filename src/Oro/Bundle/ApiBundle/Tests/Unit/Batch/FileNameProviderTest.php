<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\FileNameProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new FileNameProvider();
    }

    public function testGetDataFileName()
    {
        $fileName = $this->provider->getDataFileName();
        self::assertStringStartsWith('api_', $fileName);
        self::assertTrue(strlen($fileName) > strlen('api_'));
        self::assertNotEquals($fileName, $this->provider->getDataFileName());
    }

    public function testGetInfoFileName()
    {
        self::assertEquals('api_123_info', $this->provider->getInfoFileName(123));
    }

    public function testGetChunkIndexFileName()
    {
        self::assertEquals('api_123_chunk_index', $this->provider->getChunkIndexFileName(123));
    }

    public function testGetChunkJobIndexFileName()
    {
        self::assertEquals('api_123_chunk_job_index', $this->provider->getChunkJobIndexFileName(123));
    }

    public function testGetChunkFileNameTemplate()
    {
        self::assertEquals('api_123_chunk_%s', $this->provider->getChunkFileNameTemplate(123));
    }

    public function testGetChunkErrorsFileName()
    {
        self::assertEquals(
            'api_123_chunk_test_errors',
            $this->provider->getChunkErrorsFileName('api_123_chunk_test')
        );
    }

    public function testGetErrorIndexFileName()
    {
        self::assertEquals('api_123_error_index', $this->provider->getErrorIndexFileName(123));
    }

    public function testGetIncludeIndexFileName()
    {
        self::assertEquals('api_123_include_index', $this->provider->getIncludeIndexFileName(123));
    }

    public function testGetProcessedIncludeIndexFileName()
    {
        self::assertEquals('api_123_include_index_processed', $this->provider->getProcessedIncludeIndexFileName(123));
    }

    public function testGetLinkedIncludeIndexFileName()
    {
        self::assertEquals('api_123_include_index_linked', $this->provider->getLinkedIncludeIndexFileName(123));
    }

    public function testGetLockFileName()
    {
        self::assertEquals('test.lock', $this->provider->getLockFileName('test'));
    }

    public function testGetFilePrefix()
    {
        self::assertEquals('api_321_', $this->provider->getFilePrefix(321));
    }
}
