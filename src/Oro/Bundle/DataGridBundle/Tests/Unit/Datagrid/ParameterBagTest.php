<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class ParameterBagTest extends \PHPUnit_Framework_TestCase
{
    public function testAll()
    {
        $data = array('foo' => 'bar');

        $parameters = $this->createParameterBag($data);

        $this->assertEquals($data, $parameters->all());
    }

    public function testKeys()
    {
        $data = array('foo' => 'bar');

        $parameters = $this->createParameterBag($data);

        $this->assertEquals(array('foo'), $parameters->keys());
    }

    public function testReplace()
    {
        $data = array('foo' => 'bar');
        $replaceData = array('bar' => 'baz');

        $parameters = $this->createParameterBag($data);
        $parameters->replace($replaceData);

        $this->assertEquals($replaceData, $parameters->all());
    }

    public function testAdd()
    {
        $data = array('one' => array('one' => 'origin', 'two' => 'origin'), 'two' => 'origin');
        $addData = array('one' => array('one' => 'new'), 'two' => 'new');
        $expectedData = array('one' => array('one' => 'new', 'two' => 'origin'), 'two' => 'new');

        $parameters = $this->createParameterBag($data);
        $parameters->add($addData);

        $this->assertEquals($expectedData, $parameters->all());
    }

    public function testGet()
    {
        $data = array('foo' => 'bar');

        $parameters = $this->createParameterBag($data);

        $this->assertEquals($data['foo'], $parameters->get('foo'));
        $this->assertEquals('baz', $parameters->get('bar', 'baz'));
    }

    public function testSet()
    {
        $data = array();

        $parameters = $this->createParameterBag($data);
        $parameters->set('foo', 'bar');

        $this->assertEquals(array('foo' => 'bar'), $parameters->all());
    }

    public function testMergeKey()
    {
        $data = array('one' => array('one' => 'origin', 'two' => 'origin'), 'two' => 'origin');
        $mergeKey = 'one';
        $mergeKeyData = array('one' => 'new');
        $expectedData = array('one' => array('one' => 'new', 'two' => 'origin'), 'two' => 'origin');

        $parameters = $this->createParameterBag($data);
        $parameters->mergeKey($mergeKey, $mergeKeyData);

        $this->assertEquals($expectedData, $parameters->all());
    }

    public function testHas()
    {
        $data = array('foo' => 'bar');

        $parameters = $this->createParameterBag($data);

        $this->assertTrue($parameters->has('foo'));
        $this->assertFalse($parameters->has('bar'));
    }

    public function testRemove()
    {
        $data = array('foo' => 'bar');

        $parameters = $this->createParameterBag($data);

        $parameters->remove('foo');

        $this->assertFalse($parameters->has('foo'));
    }

    protected function createParameterBag(array $params)
    {
        return new ParameterBag($params);
    }
}
