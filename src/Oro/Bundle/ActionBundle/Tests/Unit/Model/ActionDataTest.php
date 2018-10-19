<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ActionDataTest extends \PHPUnit\Framework\TestCase
{
    public function testOffsetExistsSetGetUnset()
    {
        $data = new ActionData();
        $name = 'foo';
        $value = 'bar';

        $this->assertFalse(isset($data[$name]));
        $this->assertNull($data[$name]);

        $data[$name] = $value;
        $this->assertTrue(isset($data[$name]));
        $this->assertEquals($value, $data[$name]);

        unset($data[$name]);
        $this->assertFalse(isset($data[$name]));
        $this->assertNull($data[$name]);
    }

    public function testIssetGetSetUnset()
    {
        $data = new ActionData();

        $this->assertFalse(isset($data->foo));
        $this->assertNull($data->foo);

        $data->foo = 'bar';
        $this->assertTrue(isset($data->foo));
        $this->assertEquals('bar', $data->foo);

        unset($data->foo);
        $this->assertFalse(isset($data->foo));
        $this->assertNull($data->foo);
    }

    public function testGetEntity()
    {
        $data = new ActionData();
        $this->assertNull($data->getEntity());

        $data['data'] = new \stdClass();
        $this->assertInternalType('object', $data->getEntity());
    }

    public function testGetIterator()
    {
        $array = ['foo' => 'bar', 'bar' => 'foo'];
        $data = new ActionData($array);
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertEquals($array, $result);
    }

    public function testCount()
    {
        $data = new ActionData();
        $this->assertEquals(0, count($data));

        $data['foo'] = 'bar';
        $this->assertEquals(1, count($data));

        $data['bar'] = 'foo';
        $this->assertEquals(2, count($data));

        unset($data['foo']);
        $this->assertEquals(1, count($data));

        unset($data['bar']);
        $this->assertEquals(0, count($data));
    }

    public function testIsModified()
    {
        $data1 = new ActionData(['foo' => 'bar']);
        $this->assertFalse($data1->isModified());

        $data1['foo'] = 'bar';
        $this->assertFalse($data1->isModified());

        $data2 = clone $data1;

        $data1['foo'] = null;
        $this->assertTrue($data1->isModified());

        unset($data2['foo']);
        $this->assertTrue($data1->isModified());
    }

    public function testIsEmpty()
    {
        $data = new ActionData();
        $this->assertTrue($data->isEmpty());

        $data->offsetSet('foo', 'bar');
        $this->assertFalse($data->isEmpty());
    }

    public function testToArray()
    {
        $array = ['foo' => 'bar', 'bar' => 'foo'];

        $data = new ActionData($array);
        $this->assertEquals($array, $data->toArray());
    }

    public function testGetRedirectUrl()
    {
        $data = new ActionData();
        $this->assertNull($data->getRedirectUrl());

        $url = 'my/test/url';

        $data->offsetSet('redirectUrl', $url);
        $this->assertEquals($url, $data->getRedirectUrl());
    }

    public function testGetRefreshGrid()
    {
        $data = new ActionData();
        $this->assertNull($data->getRefreshGrid());

        $name = 'test_grid_dame';

        $data->offsetSet('refreshGrid', $name);
        $this->assertEquals($name, $data->getRefreshGrid());
    }

    public function testGetValues()
    {
        $date = new \DateTime();
        $array = ['foo' => 'bar', 'bar' => 'foo', 'baz' => $date, 'tango' => null];

        $data = new ActionData($array);
        $this->assertEquals($array, $data->getValues());

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => $date, 'tango' => null, 'test' => null],
            $data->getValues(['foo', 'baz', 'tango', 'test'])
        );
    }

    public function testGetScalarValues()
    {
        $data = new ActionData([
            'key1' => ['param1'],
            'key2' => 'value2',
            'key3' => 3,
            'key4' => new \stdClass(),
        ]);

        $this->assertEquals(['key2' => 'value2', 'key3' => 3], $data->getScalarValues());
    }
}
