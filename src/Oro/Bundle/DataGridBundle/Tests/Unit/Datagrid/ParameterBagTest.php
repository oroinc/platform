<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class ParameterBagTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $data = ['foo' => 'bar'];

        $parameters = new ParameterBag($data);

        $this->assertEquals($data, $parameters->all());
    }

    public function testKeys()
    {
        $data = ['foo' => 'bar'];

        $parameters = new ParameterBag($data);

        $this->assertEquals(['foo'], $parameters->keys());
    }

    public function testReplace()
    {
        $data = ['foo' => 'bar'];
        $replaceData = ['bar' => 'baz'];

        $parameters = new ParameterBag($data);
        $parameters->replace($replaceData);

        $this->assertEquals($replaceData, $parameters->all());
    }

    public function testAdd()
    {
        $data = ['one' => ['one' => 'origin', 'two' => 'origin'], 'two' => 'origin'];
        $addData = ['one' => ['one' => 'new'], 'two' => 'new'];
        $expectedData = ['one' => ['one' => 'new', 'two' => 'origin'], 'two' => 'new'];

        $parameters = new ParameterBag($data);
        $parameters->add($addData);

        $this->assertEquals($expectedData, $parameters->all());
    }

    public function testGet()
    {
        $data = ['foo' => 'bar'];

        $parameters = new ParameterBag($data);

        $this->assertEquals($data['foo'], $parameters->get('foo'));
        $this->assertEquals('baz', $parameters->get('bar', 'baz'));
    }

    public function testSet()
    {
        $data = [];

        $parameters = new ParameterBag($data);
        $parameters->set('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $parameters->all());
    }

    public function testMergeKey()
    {
        $data = ['one' => ['one' => 'origin', 'two' => 'origin'], 'two' => 'origin'];
        $mergeKey = 'one';
        $mergeKeyData = ['one' => 'new'];
        $expectedData = ['one' => ['one' => 'new', 'two' => 'origin'], 'two' => 'origin'];

        $parameters = new ParameterBag($data);
        $parameters->mergeKey($mergeKey, $mergeKeyData);

        $this->assertEquals($expectedData, $parameters->all());
    }

    public function testHas()
    {
        $data = ['foo' => 'bar'];

        $parameters = new ParameterBag($data);

        $this->assertTrue($parameters->has('foo'));
        $this->assertFalse($parameters->has('bar'));
    }

    public function testRemove()
    {
        $data = ['foo' => 'bar'];

        $parameters = new ParameterBag($data);

        $parameters->remove('foo');

        $this->assertFalse($parameters->has('foo'));
    }
}
