<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutItem;
use Oro\Component\Layout\RawLayoutBuilder;

class LayoutItemTest extends \PHPUnit\Framework\TestCase
{
    private RawLayoutBuilder $rawLayoutBuilder;

    private LayoutContext $context;

    private LayoutItem $item;

    protected function setUp(): void
    {
        $this->rawLayoutBuilder = new RawLayoutBuilder();
        $this->context = new LayoutContext();

        $this->item = new LayoutItem(
            $this->rawLayoutBuilder,
            $this->context
        );
    }

    public function testGetContext(): void
    {
        self::assertSame($this->context, $this->item->getContext());
    }

    public function testInitialize(): void
    {
        $id    = 'test_id';
        $alias = 'test_alias';

        $this->item->initialize($id, $alias);

        self::assertEquals($id, $this->item->getId());
        self::assertEquals($alias, $this->item->getAlias());
    }

    public function testGetTypeName(): void
    {
        $id        = 'test_id';
        $blockType = 'test_block_type';

        $this->rawLayoutBuilder->add($id, null, $blockType);

        $this->item->initialize($id);

        self::assertEquals($blockType, $this->item->getTypeName());
    }

    public function testGetOptions(): void
    {
        $id      = 'test_id';
        $options = ['foo' => 'bar'];

        $this->rawLayoutBuilder->add($id, null, 'test', $options);

        $this->item->initialize($id);

        self::assertEquals($options, $this->item->getOptions());
    }

    public function testGetParentId(): void
    {
        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('header', 'root', 'header');

        $this->item->initialize('header');

        self::assertEquals('root', $this->item->getParentId());
    }

    public function testGetRootIdWhenIsEmpty(): void
    {
        $this->item->initialize('sample_id');

        self::assertNull($this->item->getRootId());
    }

    public function testGetRootIdWhenNotEmpty(): void
    {
        $this->item->initialize('sample_id');

        $this->rawLayoutBuilder
            ->add('root', null, 'root')
            ->add('sample_id', 'root', 'text');

        self::assertEquals('root', $this->item->getRootId());
    }
}
