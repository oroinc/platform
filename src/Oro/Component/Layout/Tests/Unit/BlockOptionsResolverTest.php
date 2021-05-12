<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;

class BlockOptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var BlockOptionsResolver */
    private $blockOptionsResolver;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(LayoutRegistryInterface::class);

        $this->blockOptionsResolver = new BlockOptionsResolver($this->registry);
    }

    public function testResolveOptionsByBlockName()
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

    public function testResolveOptionsByAlreadyCreatedBlockTypeObject()
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

    public function testResolveOptionsForBaseTypeByBlockName()
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

    public function testResolveOptionsForBaseTypeByAlreadyCreatedBlockTypeObject()
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
