<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\LayoutContext;

class ContextDataCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var ContextDataCollection */
    protected $collection;

    protected function setUp()
    {
        $this->context    = new LayoutContext();
        $this->collection = new ContextDataCollection($this->context);
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetSetHasRemove($value)
    {
        $this->assertFalse(
            $this->collection->has('test'),
            'Failed asserting that data do not exist'
        );
        $this->collection->set('test', 'data_id', $value);
        $this->assertTrue(
            $this->collection->has('test'),
            'Failed asserting that data exist'
        );
        $this->assertSame(
            $value,
            $this->collection->get('test'),
            'Failed asserting that added data equal to the value returned by "get" method'
        );
        $this->assertSame(
            'data_id',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier is the same as passed to "set" method'
        );

        $this->collection->remove('test');
        $this->assertFalse(
            $this->collection->has('test'),
            'Failed asserting that data were removed'
        );
    }

    public function valueDataProvider()
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

    public function testSetDefault()
    {
        $this->context['data_id'] = 'dataId';
        $this->context['data']    = 'data';

        $this->collection->setDefault(
            'test',
            function ($options) {
                return $options['data_id'];
            },
            function ($options) {
                return $options['data'];
            }
        );

        $this->assertEquals(
            ['test'],
            $this->collection->getKnownValues(),
            'Failed asserting that getKnownValues returns expected values'
        );
        $this->assertTrue(
            $this->collection->has('test'),
            'Failed asserting that data exist'
        );
        $this->assertSame(
            'data',
            $this->collection->get('test'),
            'Failed asserting that added data equal to the expected value'
        );
        $this->assertSame(
            'dataId',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier equals to the expected value'
        );

        $this->collection->set('test', 'updatedDataId', 'updatedData');
        $this->assertEquals(
            ['test'],
            $this->collection->getKnownValues(),
            'Failed asserting that getKnownValues does not return duplicates'
        );
        $this->assertSame(
            'updatedData',
            $this->collection->get('test'),
            'Failed asserting that added data equal to the value returned by "get" method'
        );
        $this->assertSame(
            'updatedDataId',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier is the same as passed to "set" method'
        );

        $this->collection->remove('test');
        $this->assertTrue(
            $this->collection->has('test'),
            'Failed asserting that default data exist after remove'
        );
        $this->assertSame(
            'data',
            $this->collection->get('test'),
            'Failed asserting that added data equal to the expected value after remove'
        );
        $this->assertSame(
            'dataId',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier equals to the expected value after remove'
        );
    }

    public function testSetDefaultWhenDataCannotBeLoaded()
    {
        $this->collection->setDefault(
            'test',
            function () {
                return 'dataId';
            },
            function () {
                throw new \BadMethodCallException();
            }
        );

        $this->assertFalse($this->collection->has('test'));
    }

    public function testSetDefaultScalar()
    {
        $this->collection->setDefault('test', 'dataId', 'data');
        $this->assertSame(
            'data',
            $this->collection->get('test'),
            'Failed asserting that added data equal to the expected value'
        );
        $this->assertSame(
            'dataId',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier equals to the expected value'
        );
    }

    public function testSetDefaultCallable()
    {
        $this->context['data_id'] = 'dataId';
        $this->context['data']    = 'data';

        $this->collection->setDefault(
            'test',
            [$this, 'getTestDataIdentifier'],
            [$this, 'getTestDataValue']
        );

        $this->assertSame(
            'data',
            $this->collection->get('test'),
            'Failed asserting that added data equal to the expected value'
        );
        $this->assertSame(
            'dataId',
            $this->collection->getIdentifier('test'),
            'Failed asserting that data identifier equals to the expected value'
        );
    }

    public function getTestDataIdentifier($options)
    {
        return $options['data_id'];
    }

    public function getTestDataValue($options)
    {
        return $options['data'];
    }
}
