<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\RawLayoutBuilder;

class LayoutItemTest extends \PHPUnit_Framework_TestCase
{
    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var LayoutContext */
    protected $context;

    /** @var LayoutItem */
    protected $item;

    protected function setUp()
    {
        $this->rawLayoutBuilder = new RawLayoutBuilder();
        $this->context          = new LayoutContext();
        $this->item             = new LayoutItem(
            $this->rawLayoutBuilder,
            $this->context
        );
    }

    public function testGetContext()
    {
        $this->assertSame($this->context, $this->item->getContext());
    }

    public function testInitialize()
    {
        $id    = 'test_id';
        $alias = 'test_alias';

        $this->item->initialize($id, $alias);

        $this->assertEquals($id, $this->item->getId());
        $this->assertEquals($alias, $this->item->getAlias());
    }

    public function testGetTypeName()
    {
        $id        = 'test_id';
        $blockType = 'test_block_type';

        $this->rawLayoutBuilder->add($id, null, $blockType);

        $this->item->initialize($id);

        $this->assertEquals($blockType, $this->item->getTypeName());
    }

    public function testGetOptions()
    {
        $id      = 'test_id';
        $options = ['foo' => 'bar'];

        $this->rawLayoutBuilder->add($id, null, 'test', $options);

        $this->item->initialize($id);

        $this->assertEquals($options, $this->item->getOptions());
    }

    public function testGetParentId()
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header');

        $this->item->initialize('header');

        $this->assertEquals('root', $this->item->getParentId());
    }
}
