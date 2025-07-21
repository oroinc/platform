<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonsCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\ButtonCollectionMapException;
use Oro\Bundle\ActionBundle\Extension\ButtonProviderExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ButtonsCollectionTest extends TestCase
{
    private ButtonSearchContext&MockObject $searchContext;
    private ButtonsCollection $collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchContext = $this->createMock(ButtonSearchContext::class);

        $this->collection = new ButtonsCollection();
    }

    public function testConsume(): void
    {
        $extension1 = $this->getExtension([$this->getButton(1), $this->getButton(1)]);
        $extension2 = $this->getExtension([$this->getButton(1)]);
        $extension3 = $this->getExtension();

        $this->assertEmpty($this->collection);

        $this->collection->consume($extension1, $this->searchContext);
        $this->collection->consume($extension2, $this->searchContext);
        $this->collection->consume($extension3, $this->searchContext);

        $this->assertCount(3, $this->collection);
    }

    public function testToArray(): void
    {
        $buttons = [$this->getButton(1), $this->getButton(1)];
        $this->collection->consume($this->getExtension($buttons), $this->searchContext);

        $this->assertEquals($buttons, $this->collection->toArray());
    }

    public function testToList(): void
    {
        $button1 = $this->getButton(1);
        $button2 = $this->getButton(2);
        $extension = $this->getExtension([$button2, $button1]);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertSame([$button1, $button2], $this->collection->toList(), 'Must be ordered list.');
    }

    public function testFilter(): void
    {
        $button1 = $this->getButton(1);
        $button2 = $this->getButton(2);
        $extension = $this->getExtension([$button2, $button1]);
        $this->collection->consume($extension, $this->searchContext);

        $filtered = $this->collection->filter(function (ButtonInterface $button) {
            return $button->getOrder() === 2;
        });

        $this->assertInstanceOf(ButtonsCollection::class, $filtered, 'Instance of ButtonsCollection expected.');
        $this->assertNotSame($this->collection, $filtered, 'New instance expected.');

        $this->assertSame([$button2], $filtered->toArray(), 'Same buttons instances but filtered.');
    }

    public function testMap(): void
    {
        $button1 = $this->getButton(2);
        $button2 = $this->getButton(1);
        $extension = $this->getExtension([$button1, $button2]);
        $this->collection->consume($extension, $this->searchContext);

        $mapped = $this->collection->map(function (ButtonInterface $button) {
            return clone $button;
        });

        $this->assertInstanceOf(ButtonsCollection::class, $mapped, 'Instance of ButtonStorage expected.');
        $this->assertNotSame($this->collection, $mapped, 'New instance expected.');

        $this->assertNotSame([$button1, $button2], $mapped->toArray(), 'Cloned buttons expected.');
        $this->assertEquals([$button1, $button2], $mapped->toArray(), 'Buttons are equals by data.');
    }

    public function testMapException(): void
    {
        $button = $this->getButton(1);
        $extension = $this->getExtension([$button]);
        $this->collection->consume($extension, $this->searchContext);

        $this->expectException(ButtonCollectionMapException::class);
        $this->expectExceptionMessage(sprintf(
            'Map callback should return `%s` as result got `%s` instead.',
            ButtonInterface::class,
            \stdClass::class
        ));

        $this->collection->map(
            function () {
                return new \stdClass();
            }
        );
    }

    public function testGetIterator(): void
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->collection->getIterator());
    }

    public function testCount(): void
    {
        $buttons = [$this->getButton(1), $this->getButton(1)];
        $extension = $this->getExtension($buttons);
        $this->collection->consume($extension, $this->searchContext);

        $this->assertEquals(2, $this->collection->count());
    }

    private function getButton(int $order): ButtonInterface
    {
        $button = $this->createMock(ButtonInterface::class);
        $button->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        return $button;
    }

    /**
     * @param ButtonInterface[] $buttons
     *
     * @return ButtonProviderExtensionInterface
     */
    private function getExtension(array $buttons = []): ButtonProviderExtensionInterface
    {
        $extension = $this->createMock(ButtonProviderExtensionInterface::class);
        $extension->expects($this->any())
            ->method('find')
            ->with($this->searchContext)
            ->willReturn($buttons);

        return $extension;
    }
}
