<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextItemInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\LayoutContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LayoutContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutContext */
    private $context;

    protected function setUp(): void
    {
        $this->context = new LayoutContext();
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetSetHasRemove(mixed $value)
    {
        $this->assertFalse(
            $this->context->has('test'),
            'Failed asserting that a value does not exist in the context'
        );
        $this->assertFalse(
            isset($this->context['test']),
            'Failed asserting that a value does not exist in the context (ArrayAccess)'
        );
        $this->context->set('test', $value);
        $this->assertTrue(
            $this->context->has('test'),
            'Failed asserting that a value exists in the context'
        );
        $this->assertTrue(
            isset($this->context['test']),
            'Failed asserting that a value does not exist in the context (ArrayAccess)'
        );
        $this->assertSame(
            $value,
            $this->context->get('test'),
            'Failed asserting that added to the context value equals to the value returned by "get" method'
        );
        $this->assertSame(
            $value,
            $this->context['test'],
            'Failed asserting that added to the context value equals to the value returned by ArrayAccess get'
        );

        $this->context['test1'] = $value;
        $this->assertSame(
            $value,
            $this->context->get('test1'),
            'Failed asserting that set by ArrayAccess value equals to the value returned by "get" method'
        );
        $this->assertSame(
            $value,
            $this->context['test1'],
            'Failed asserting that set by ArrayAccess value equals to the value returned by ArrayAccess get'
        );

        $this->context->remove('test');
        $this->assertFalse(
            $this->context->has('test'),
            'Failed asserting that a value was removed the context'
        );
        unset($this->context['test1']);
        $this->assertFalse(
            $this->context->has('test1'),
            'Failed asserting that a value was removed the context (ArrayAccess)'
        );
    }

    public function valueDataProvider(): array
    {
        return [
            [null],
            [123],
            ['val'],
            [[]],
            [[1, 2, 3]],
            [new \stdClass()]
        ];
    }

    public function testResolveShouldThrowExceptionIfInvalidObjectTypeAdded()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(\sprintf(
            'Failed to resolve the context variables.'
            . ' Reason: The option "test" has invalid type. Expected "%s", but "stdClass" given.',
            ContextItemInterface::class
        ));

        $this->context->getResolver()->setDefined(['test']);
        $this->context->set('test', new \stdClass());
        $this->context->resolve();
    }

    public function testHasForUnknownItem()
    {
        $this->assertFalse($this->context->has('test'));
    }

    public function testGetUnknownItem()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined index: test.');

        $this->context->get('test');
    }

    public function testGetOr()
    {
        $this->assertNull($this->context->getOr('test'));
        $this->assertEquals(123, $this->context->getOr('test', 123));
        $this->context->set('test', 'val');
        $this->assertEquals('val', $this->context->getOr('test'));
    }

    public function testResolve()
    {
        $this->context->set('test', 'val');

        $this->context->getResolver()
            ->setDefined(['test'])
            ->setNormalizer(
                'test',
                function ($options, $val) {
                    return $val . '_normalized';
                }
            );

        $this->context->resolve();

        $this->assertEquals('val_normalized', $this->context['test']);
    }

    public function testResolveThrowsExceptionWhenInvalidData()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Failed to resolve the context variables.');

        $this->context->set('test', 'val');
        $this->context->resolve();
    }

    public function testResolveThrowsExceptionWhenDataAlreadyResolved()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The context variables are already resolved.');

        $this->context->resolve();
        $this->context->resolve();
    }

    public function testIsResolved()
    {
        $this->assertFalse($this->context->isResolved());
        $this->context->resolve();
        $this->assertTrue($this->context->isResolved());
    }

    public function testChangeValueAllowedForResolvedData()
    {
        $this->context->getResolver()->setDefaults(['test' => 'default']);
        $this->context->resolve();
        $this->assertEquals('default', $this->context['test']);

        $this->context->set('test', 'Updated');
        $this->assertEquals('Updated', $this->context['test']);
    }

    public function testAddNewValueThrowsExceptionWhenDataAlreadyResolved()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The item "test" cannot be added because the context variables are already resolved.'
        );

        $this->context->resolve();
        $this->context->set('test', 'Updated');
    }

    public function testRemoveNotExistingValueNotThrowsExceptionForResolvedData()
    {
        $this->context->getResolver()->setDefaults(['test' => 'default']);
        $this->context->resolve();

        $this->context->remove('unknown');
    }

    public function testRemoveExistingValueThrowsExceptionWhenDataAlreadyResolved()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The item "test" cannot be removed because the context variables are already resolved.'
        );

        $this->context->getResolver()->setDefined(['test']);
        $this->context->set('test', 'val');
        $this->context->resolve();

        $this->context->remove('test');
    }

    public function testGetData()
    {
        $this->assertInstanceOf(ContextDataCollection::class, $this->context->data());
    }

    public function testGetHash()
    {
        $this->context->resolve();
        $hash = $this->context->getHash();

        $this->assertEquals(md5(serialize([]) . serialize([])), $hash);
    }

    public function testGetHashWithContextItemInterfaceDescendantItems()
    {
        $item = $this->createMock(ContextItemInterface::class);
        $item->expects($this->once())
            ->method('getHash')
            ->willReturn('value');

        $this->context->getResolver()->setDefined(['item']);
        $this->context->set('item', $item);
        $this->context->resolve();

        $this->assertEquals(md5(serialize(['item' => 'value']) . serialize([])), $this->context->getHash());
    }

    public function testGetHashThrowAnException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The context is not resolved.');

        $this->context->getHash();
    }
}
