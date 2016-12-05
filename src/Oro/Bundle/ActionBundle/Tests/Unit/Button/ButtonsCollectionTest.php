<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;

class ButtonsCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ButtonsCollection */
    protected $collection;

    /** @var ButtonSearchContext|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->collection->consume($extension1, $this->searchContext);
        $this->collection->consume($extension2, $this->searchContext);
        $this->collection->consume($extension3, $this->searchContext);

        $this->assertCount(3, $this->collection);
    }

    public function testToArray()
    {
        $buttons = [$this->getButtonMock(), $this->getButtonMock()];
        $extension = $this->getExtensionMock($buttons);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertEquals($buttons, $this->collection->toArray());
    }

    public function testFilterAvailable()
    {
        $buttons = [$this->getButtonMock(), $this->getButtonMock()];
        $extension = $this->getExtensionMock($buttons);

        $extension->expects($this->at(1))->method('isAvailable')->willReturn(true);
        $extension->expects($this->at(2))->method('isAvailable')->willReturn(false);

        $this->collection->consume($extension, $this->searchContext);
        $filtered = $this->collection->filterAvailable($this->searchContext);
        $this->assertInstanceOf(ButtonsCollection::class, $filtered);
        $this->assertCount(1, $filtered);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->collection->getIterator());
    }

    public function testToString()
    {
        $this->assertEquals(
            ButtonsCollection::class . '@' . spl_object_hash($this->collection),
            (string)$this->collection
        );
    }

    public function testCount()
    {
        $buttons = [$this->getButtonMock(), $this->getButtonMock()];
        $extension = $this->getExtensionMock($buttons);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertEquals(2, $this->collection->count());
    }

    /**
     * @return ButtonInterface|\PHPUnit_Framework_MockObject_MockObject
     * @throws \PHPUnit_Framework_Exception
     */
    protected function getButtonMock()
    {
        $button = $this->getMockBuilder(ButtonInterface::class)->getMockForAbstractClass();
        $button->expects($this->any())->method('getOrder')->willReturn(1);

        return $button;
    }

    /**
     * @param ButtonInterface[] $buttons
     *
     * @return ButtonProviderExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getExtensionMock(array $buttons = [])
    {
        $extension = $this->getMockBuilder(ButtonProviderExtensionInterface::class)->getMockForAbstractClass();
        $extension->expects($this->any())->method('find')->with($this->searchContext)->willReturn($buttons);

        return $extension;
    }
}
