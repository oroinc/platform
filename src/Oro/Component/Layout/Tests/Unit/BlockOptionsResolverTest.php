<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\Tests\Unit\Fixtures\Layout\Block\Type\LogoType;

class BlockOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    protected function setUp()
    {
        $this->blockTypeRegistry    = $this->getMock('Oro\Component\Layout\BlockTypeRegistryInterface');
        $this->blockOptionsResolver = new BlockOptionsResolver($this->blockTypeRegistry);
    }

    public function testResolveOptionsByBlockName()
    {
        $this->blockTypeRegistry->expects($this->at(0))
            ->method('getBlockType')
            ->with('logo')
            ->will($this->returnValue(new LogoType()));
        $this->blockTypeRegistry->expects($this->at(1))
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue(new BaseType()));
        $this->blockTypeRegistry->expects($this->exactly(2))
            ->method('getBlockType');

        $result = $this->blockOptionsResolver->resolve(
            'logo',
            ['translation_domain' => 'test', 'title' => 'test_title']
        );
        $this->assertEquals('test', $result['translation_domain']);
        $this->assertEquals('test_title', $result['title']);
    }

    public function testResolveOptionsByAlreadyCreatedBlockTypeObject()
    {
        $this->blockTypeRegistry->expects($this->once())
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue(new BaseType()));

        $result = $this->blockOptionsResolver->resolve(
            new LogoType(),
            ['translation_domain' => 'test', 'title' => 'test_title']
        );
        $this->assertEquals('test', $result['translation_domain']);
        $this->assertEquals('test_title', $result['title']);
    }

    public function testResolveOptionsForBaseTypeByBlockName()
    {
        $this->blockTypeRegistry->expects($this->once())
            ->method('getBlockType')
            ->with(BaseType::NAME)
            ->will($this->returnValue(new BaseType()));

        $result = $this->blockOptionsResolver->resolve(
            BaseType::NAME,
            ['translation_domain' => 'test']
        );
        $this->assertEquals('test', $result['translation_domain']);
    }

    public function testResolveOptionsForBaseTypeByAlreadyCreatedBlockTypeObject()
    {
        $this->blockTypeRegistry->expects($this->never())
            ->method('getBlockType');

        $result = $this->blockOptionsResolver->resolve(
            new BaseType(),
            ['translation_domain' => 'test']
        );
        $this->assertEquals('test', $result['translation_domain']);
    }
}
