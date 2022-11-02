<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\ItemInterface;

class BlockViewCacheTest extends LayoutTestCase
{
    private const CONTEXT_HASH_VALUE = 'context_hash_value';

    /** @var BlockView */
    private $blockView;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var BlockViewCache */
    private $blockViewCache;

    protected function setUp(): void
    {
        $this->blockView = new BlockView();
        $this->cacheProvider = $this->createMock(AbstractAdapter::class);

        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);
        $normalizer->setSerializer($serializer);

        $this->blockViewCache = new BlockViewCache($this->cacheProvider, $serializer);
    }

    public function testSave()
    {
        $context = $this->createMock(LayoutContext::class);

        $context->expects(self::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(self::once())
            ->method('get')
            ->with($this::CONTEXT_HASH_VALUE)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->blockViewCache->save($context, $this->blockView);
    }

    public function testFetchNonCached()
    {
        $context = $this->createMock(LayoutContext::class);

        $context->expects(self::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(self::once())
            ->method('get')
            ->with($this::CONTEXT_HASH_VALUE)
            ->willReturn(null);

        $this->assertNull($this->blockViewCache->fetch($context));
    }

    public function testFetchCached()
    {
        $context = $this->createMock(LayoutContext::class);

        $context->expects(self::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $this->cacheProvider->expects(self::once())
            ->method('get')
            ->with($this::CONTEXT_HASH_VALUE)
            ->willReturn('[]');

        $context->expects(self::once())
            ->method('getHash')
            ->willReturn($this::CONTEXT_HASH_VALUE);

        $fetchedBlockView = $this->blockViewCache->fetch($context);

        $this->assertEquals($this->blockView, $fetchedBlockView);
    }

    public function testReset()
    {
        $this->cacheProvider->expects(self::once())
            ->method('clear');

        $this->blockViewCache->reset();
    }

    public function testCacheWhenContextWithFilledData()
    {
        $normalizer = $this->createMock(ObjectNormalizer::class);
        $normalizer->expects($this->any())
            ->method('supportsNormalization')
            ->willReturn(true);
        $normalizer->expects($this->any())
            ->method('supportsDenormalization')
            ->willReturn(true);
        $normalizer->expects($this->any())
            ->method('normalize')
            ->willReturnCallback(function ($data) {
                return $data->vars;
            });
        $normalizer->expects($this->any())
            ->method('denormalize')
            ->willReturnCallback(function ($data) {
                if (!$data) {
                    return null;
                }

                $object = new BlockView();
                $object->vars = $data;

                return $object;
            });
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        $cache = new BlockViewCache(
            new ArrayAdapter(0, false),
            $serializer
        );
        $context = new LayoutContext(['some data']);
        $firstBlockView = new BlockView();
        $firstBlockView->vars = ['attr' => 'first block view data'];
        $secondContext = new LayoutContext(['some data']);
        $secondContext->data()->set('custom_data_key', 'custom_data_value');
        $secondBlockView = new BlockView();
        $secondBlockView->vars = ['attr' => 'second block view data'];

        $context->getResolver()->setDefined([0]);
        $context->resolve();
        $secondContext->getResolver()->setDefined([0]);
        $secondContext->resolve();

        $cache->save($context, $firstBlockView);
        $cache->save($secondContext, $secondBlockView);

        self::assertEquals($firstBlockView, $cache->fetch($context));
        self::assertEquals($secondBlockView, $cache->fetch($secondContext));

        $secondContextWithAdditionalData = new LayoutContext(['some data']);
        $secondContextWithAdditionalData->data()->set('custom_data_key', 'custom_data_value');
        $secondContextWithAdditionalData->data()->set('additional_data_key', 'additional_data_value');
        $secondContextWithAdditionalData->getResolver()->setDefined([0]);
        $secondContextWithAdditionalData->resolve();
        self::assertNull($cache->fetch($secondContextWithAdditionalData));
    }
}
