<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Oro\Bundle\ApiBundle\Batch\ChunkSizeProvider;
use PHPUnit\Framework\TestCase;

class ChunkSizeProviderTest extends TestCase
{
    private ChunkSizeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ChunkSizeProvider(
            100,
            ['Test\Entity1' => 10],
            200,
            ['Test\Entity1' => 20]
        );
    }

    public function testGetChunkSizeForEntityWithoutOwnChunkSize(): void
    {
        self::assertSame(100, $this->provider->getChunkSize('Test\Entity2'));
    }

    public function testGetChunkSizeForEntityWithOwnChunkSize(): void
    {
        self::assertSame(10, $this->provider->getChunkSize('Test\Entity1'));
    }

    public function testGetIncludedDataChunkSizeForEntityWithoutOwnChunkSize(): void
    {
        self::assertSame(200, $this->provider->getIncludedDataChunkSize('Test\Entity2'));
    }

    public function testGetIncludedDataChunkSizeForEntityWithOwnChunkSize(): void
    {
        self::assertSame(20, $this->provider->getIncludedDataChunkSize('Test\Entity1'));
    }
}
