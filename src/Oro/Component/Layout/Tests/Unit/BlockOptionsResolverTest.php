<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BlockOptionsResolverTest extends TestCase
{
    private LayoutRegistryInterface&MockObject $registry;
    private BlockOptionsResolver $blockOptionsResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);

        $this->blockOptionsResolver = new BlockOptionsResolver($this->registry);
    }

    public function testResolveOptionsByBlockName(): void
    {
        $this->registry->expects($this->exactly(2))
            ->method('getType')
            ->withConsecutive(['logo'], [BaseType::NAME])
            ->willReturnOnConsecutiveCalls(new LogoType(), new BaseType());

        $result = $this->blockOptionsResolver->resolveOptions(
            'logo',
            ['translation_domain' => 'test', 'title' => 'test_title']
        );
        $this->assertEquals('test', $result['translation_domain']);
        $this->assertEquals('test_title', $result['title']);
    }

    public function testResolveOptionsByAlreadyCreatedBlockTypeObject(): void
    {
        $this->registry->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->willReturn(new BaseType());

        $result = $this->blockOptionsResolver->resolveOptions(
            new LogoType(),
            ['translation_domain' => 'test', 'title' => 'test_title']
        );
        $this->assertEquals('test', $result['translation_domain']);
        $this->assertEquals('test_title', $result['title']);
    }

    public function testResolveOptionsForBaseTypeByBlockName(): void
    {
        $this->registry->expects($this->once())
            ->method('getType')
            ->with(BaseType::NAME)
            ->willReturn(new BaseType());

        $result = $this->blockOptionsResolver->resolveOptions(
            BaseType::NAME,
            ['translation_domain' => 'test']
        );
        $this->assertEquals('test', $result['translation_domain']);
    }

    public function testResolveOptionsForBaseTypeByAlreadyCreatedBlockTypeObject(): void
    {
        $this->registry->expects($this->never())
            ->method('getType');

        $result = $this->blockOptionsResolver->resolveOptions(
            new BaseType(),
            ['translation_domain' => 'test']
        );
        $this->assertEquals('test', $result['translation_domain']);
    }
}
