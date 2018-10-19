<?php

namespace Oro\Component\Layout\Tests\Unit;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class BlockViewCacheTest extends LayoutTestCase
{
    /** @var BlockView */
    protected $blockView;

    /**
     * @var BlockViewCache
     */
    protected $blockViewCache;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheProvider;

    /**
     * @var Serializer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $serializer;

    const CONTEXT_HASH_VALUE = 'context_hash_value';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->blockView = new BlockView();

        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'contains', 'save', 'deleteAll'])->getMockForAbstractClass();

        $normalizer = new ObjectNormalizer();
        $this->serializer = new Serializer([$normalizer], [new JsonEncoder()]);
        $normalizer->setSerializer($this->serializer);

        $this->blockViewCache = new BlockViewCache($this->cacheProvider, $this->serializer);
    }

    public function testSave()
    {
        $context = $this->getMockBuilder(LayoutContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(static::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with($this::CONTEXT_HASH_VALUE, '[]');

        $this->blockViewCache->save($context, $this->blockView);
    }

    public function testFetchNonCached()
    {
        $context = $this->getMockBuilder(LayoutContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(static::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with($this::CONTEXT_HASH_VALUE)
            ->willReturn(false);

        $this->assertNull($this->blockViewCache->fetch($context));
    }

    public function testFetchCached()
    {
        $context = $this->getMockBuilder(LayoutContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(static::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with($this::CONTEXT_HASH_VALUE)
            ->willReturn('[]');

        $context->expects(static::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $fetchedBlockView = $this->blockViewCache->fetch($context);

        $this->assertEquals($this->blockView, $fetchedBlockView);
    }

    public function testReset()
    {
        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->blockViewCache->reset();
    }
}
