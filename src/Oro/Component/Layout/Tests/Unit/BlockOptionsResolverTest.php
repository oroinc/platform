<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;

class BlockOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    protected function setUp()
    {
        $this->registry             = $this->getMock('Oro\Component\Layout\LayoutRegistryInterface');
        $this->blockOptionsResolver = new BlockOptionsResolver($this->registry);
    }

    public function testResolveOptionsByBlockName()
    {
        $this->registry->expects($this->at(0))
            ->method('getType')
            ->with('logo')
            ->will($this->returnValue(new LogoType()));
        $this->registry->expects($this->at(1))
            ->method('getType')
            ->with(BaseType::NAME)
            ->will($this->returnValue(new BaseType()));
        $this->registry->expects($this->exactly(2))
            ->method('getType');

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
            ->will($this->returnValue(new BaseType()));

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
            ->will($this->returnValue(new BaseType()));

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
