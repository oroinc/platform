<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;

class ButtonsCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ButtonsCollection */
    protected $collection;

    /** @var ButtonSearchContext|\PHPUnit\Framework\MockObject\MockObject */
    protected $searchContext;

    protected function setUp()
    {
        $this->collection = new ButtonsCollection();
        $this->searchContext = $this->getMockBuilder(ButtonSearchContext::class)->getMock();
    }

    protected function tearDown()
    {
        unset($this->collection, $this->searchContext);
    }

    public function testConsume()
    {
        $extension1 = $this->getExtensionMock([$this->getButtonMock(), $this->getButtonMock()]);
        $extension2 = $this->getExtensionMock([$this->getButtonMock()]);
        $extension3 = $this->getExtensionMock();

        $this->assertEmpty($this->collection);

        $this->collection->consume($extension1, $this->searchContext);
        $this->collection->consume($extension2, $this->searchContext);
        $this->collection->consume($extension3, $this->searchContext);

        $this->assertCount(3, $this->collection);
    }

    public function testToArray()
    {
        $buttons = [$this->getButtonMock(), $this->getButtonMock()];
        $this->collection->consume($this->getExtensionMock($buttons), $this->searchContext);

        $this->assertEquals($buttons, $this->collection->toArray());
    }

    public function testToList()
    {
        $button1 = $this->getButtonMock(1);
        $button2 = $this->getButtonMock(2);
        $extension = $this->getExtensionMock([$button2, $button1]);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertSame([$button1, $button2], $this->collection->toList(), 'Must be ordered list.');
    }

    public function testFilter()
    {
        $button1 = $this->getButtonMock(1);
        $button2 = $this->getButtonMock(2);
        $extension = $this->getExtensionMock([$button2, $button1]);
        $this->collection->consume($extension, $this->searchContext);

        $filtered = $this->collection->filter(function (ButtonInterface $button) {
            return $button->getOrder() === 2;
        });

        $this->assertInstanceOf(ButtonsCollection::class, $filtered, 'Instance of ButtonsCollection expected.');
        $this->assertNotSame($this->collection, $filtered, 'New instance expected.');

        $this->assertSame([$button2], $filtered->toArray(), 'Same buttons instances but filtered.');
    }

    public function testMap()
    {
        $button1 = $this->getButtonMock(2);
        $button2 = $this->getButtonMock(1);
        $extension = $this->getExtensionMock([$button1, $button2]);
        $this->collection->consume($extension, $this->searchContext);

        $mapped = $this->collection->map(function (ButtonInterface $button) {
            return clone $button;
        });

        $this->assertInstanceOf(ButtonsCollection::class, $mapped, 'Instance of ButtonStorage expected.');
        $this->assertNotSame($this->collection, $mapped, 'New instance expected.');

        $this->assertNotSame([$button1, $button2], $mapped->toArray(), 'Cloned buttons expected.');
        $this->assertEquals([$button1, $button2], $mapped->toArray(), 'Buttons are equals by data.');
    }

    public function testMapException()
    {
        $button = $this->getButtonMock();
        $extension = $this->getExtensionMock([$button]);
        $this->collection->consume($extension, $this->searchContext);

        $this->expectException('Oro\Bundle\ActionBundle\Exception\ButtonCollectionMapException');
        $this->expectExceptionMessage(
            sprintf(
                'Map callback should return `%s` as result got `%s` instead.',
                ButtonInterface::class,
                \stdClass::class
            )
        );

        $this->collection->map(
            function () {
                return new \stdClass();
            }
        );
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->collection->getIterator());
    }

    public function testCount()
    {
        $buttons = [$this->getButtonMock(), $this->getButtonMock()];
        $extension = $this->getExtensionMock($buttons);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertEquals(2, $this->collection->count());
    }

    /**
     * @param int $order
     * @return ButtonInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getButtonMock($order = 1)
    {
        $button = $this->getMockBuilder(ButtonInterface::class)->getMockForAbstractClass();
        $button->expects($this->any())->method('getOrder')->willReturn($order);

        return $button;
    }

    /**
     * @param ButtonInterface[] $buttons
     *
     * @return ButtonProviderExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getExtensionMock(array $buttons = [])
    {
        $extension = $this->getMockBuilder(ButtonProviderExtensionInterface::class)->getMockForAbstractClass();
        $extension->expects($this->any())->method('find')->with($this->searchContext)->willReturn($buttons);

        return $extension;
    }
}
