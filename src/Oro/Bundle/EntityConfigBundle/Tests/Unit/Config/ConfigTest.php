<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testCloneConfig()
    {
        $config = new Config(new EntityConfigId('testScope', 'testClass'));

        $values = array('firstKey' => 'firstValue', 'secondKey' => new \stdClass());
        $config->setValues($values);

        $clone = clone $config;

        $this->assertTrue($config == $clone);
        $this->assertFalse($config === $clone);

    }

    public function testValueConfig()
    {
        $config = new Config(new EntityConfigId('testScope', 'testClass'));

        $values = array('firstKey' => 'firstValue', 'secondKey' => 'secondValue', 'fourthKey' => new \stdClass());
        $config->setValues($values);

        $this->assertEquals($values, $config->all());
        $this->assertEquals(
            array('firstKey' => 'firstValue'),
            $config->all(
                function ($value) {
                    return $value == 'firstValue';
                }
            )
        );

        $this->assertEquals('firstValue', $config->get('firstKey'));
        $this->assertEquals('secondValue', $config->get('secondKey'));

        $this->assertEquals(true, $config->is('secondKey'));

        $this->assertEquals(true, $config->has('secondKey'));
        $this->assertEquals(false, $config->has('thirdKey'));

        $this->assertEquals(null, $config->get('thirdKey'));

        $this->assertEquals($config, unserialize(serialize($config)));

        $config->set('secondKey', 'secondValue2');
        $this->assertEquals('secondValue2', $config->get('secondKey'));

        $this->setExpectedException('Oro\Bundle\EntityConfigBundle\Exception\RuntimeException');
        $config->get('thirdKey', true);
    }

    public function testSetState()
    {
        $configId = new EntityConfigId('testScope', 'Test\Class');
        $configValues = ['test' => 'testVal'];
        $config = Config::__set_state(
            [
                'id' => $configId,
                'values' => $configValues,
            ]
        );
        $this->assertEquals($configId, $config->getId());
        $this->assertEquals($configValues['test'], $config->get('test'));
    }
}
