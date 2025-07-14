<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;

class BlockViewCacheTest extends TestCase
{
    private AbstractAdapter&MockObject $cache;
    private SerializerInterface&MockObject $serializer;
    private BlockViewCache $blockViewCache;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(AbstractAdapter::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->blockViewCache = new BlockViewCache($this->cache, $this->serializer);
    }

    private function getContext(string $contextHash): LayoutContext
    {
        $context = $this->createMock(LayoutContext::class);
        $context->expects(self::once())
            ->method('getHash')
            ->willReturn($contextHash);

        return $context;
    }

    public function testSave(): void
    {
        $contextHash = 'context@hash';
        $context = $this->getContext($contextHash);
        $cacheKey = rawurlencode($contextHash);
        $blockView = new BlockView();
        $serializedBlockView = '{}';

        $this->cache->expects(self::once())
            ->method('delete')
            ->with($cacheKey);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($blockView), JsonEncoder::FORMAT)
            ->willReturn($serializedBlockView);

        $this->blockViewCache->save($context, $blockView);
    }

    public function testFetchNonCached(): void
    {
        $contextHash = 'context@hash';
        $context = $this->getContext($contextHash);
        $cacheKey = rawurlencode($contextHash);

        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->serializer->expects(self::never())
            ->method('deserialize');

        self::assertNull($this->blockViewCache->fetch($context));
    }

    public function testFetchCached(): void
    {
        $contextHash = 'context@hash';
        $context = $this->getContext($contextHash);
        $cacheKey = rawurlencode($contextHash);
        $cachedValue = '{}';
        $blockView = new BlockView();

        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($cachedValue);

        $this->serializer->expects(self::once())
            ->method('deserialize')
            ->with($cachedValue, BlockView::class, JsonEncoder::FORMAT, ['context_hash' => $contextHash])
            ->willReturn($blockView);

        self::assertSame($blockView, $this->blockViewCache->fetch($context));
    }

    public function testReset(): void
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->blockViewCache->reset();
    }
}
