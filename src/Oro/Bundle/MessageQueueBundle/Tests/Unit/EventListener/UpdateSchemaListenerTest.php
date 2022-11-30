<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\EventListener\UpdateSchemaListener;
use Oro\Component\Testing\TempDirExtension;
use Psr\Cache\CacheItemPoolInterface;

class UpdateSchemaListenerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $filePath;

    private int $fileModificationTime;

    private CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject $interruptConsumptionCache;

    private UpdateSchemaListener $listener;

    protected function setUp(): void
    {
        $this->filePath = $this->getTempDir('InterruptConsumptionExtensionTest')
            . DIRECTORY_SEPARATOR
            . 'interrupt.tmp';
        touch($this->filePath);
        $this->fileModificationTime = filemtime($this->filePath);

        $this->interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);

        $this->listener = new UpdateSchemaListener($this->filePath);
    }

    protected function tearDown(): void
    {
        $directory = dirname($this->filePath);

        @\unlink($this->filePath);
        @\rmdir($directory);

        self::assertDirectoryDoesNotExist($directory);
    }

    public function testInterruptConsumptionWithFile(): void
    {
        clearstatcache(true, $this->filePath);

        self::assertFileExists($this->filePath);
        self::assertEquals($this->fileModificationTime, filemtime($this->filePath));

        sleep(1);

        $this->interruptConsumptionCache->expects(self::never())
            ->method('deleteItem')
            ->withAnyParameters();

        $this->listener->interruptConsumption();

        clearstatcache(true, $this->filePath);

        self::assertFileExists($this->filePath);
        self::assertGreaterThan($this->fileModificationTime, filemtime($this->filePath));
    }

    public function testInterruptConsumptionWithCache(): void
    {
        $this->interruptConsumptionCache->expects(self::once())
            ->method('deleteItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY);

        $this->listener->setInterruptConsumptionCache($this->interruptConsumptionCache);
        $this->listener->interruptConsumption();
    }
}
