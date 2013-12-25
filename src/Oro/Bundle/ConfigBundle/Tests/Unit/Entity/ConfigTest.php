<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Config;
    }

    public function testGetId()
    {
        $this->assertNull($this->object->getId());
    }

    public function testEntity()
    {
        $object = $this->object;
        $entity = 'Oro\Entity';

        $this->assertEmpty($object->getEntity());

        $object->setEntity($entity);

        $this->assertEquals($entity, $object->getEntity());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $object->getValues());
    }

    public function testRecordId()
    {
        $object = $this->object;
        $id     = 5;

        $this->assertEmpty($object->getRecordId());

        $object->setRecordId($id);

        $this->assertEquals($id, $object->getRecordId());
    }

    /**
     * Test getOrCreateValue
     */
    public function testGetOrCreateValue()
    {
        $object   = $this->object;

        $value = $object->getOrCreateValue('oro_user', 'level');

        $this->assertEquals('oro_user', $value->getSection());
        $this->assertEquals('level', $value->getName());
        $this->assertEquals($object, $value->getConfig());

        $values = new ArrayCollection();
        $configValue = new ConfigValue();
        $configValue->setValue('test')
            ->setSection('test')
            ->setName('test');

        $values->add($configValue);
        $object->setValues($values);

        $value = $object->getOrCreateValue('test', 'test');

        $this->assertEquals('test', (string)$value);
        $this->assertEquals('test', $value->getSection());
        $this->assertEquals('test', $value->getName());
    }

    /**
     * Test Lifecycle event callbacks
     */
    public function testConfigValueLifecycleEvents()
    {
        $configValue = new ConfigValue();

        $configValue->setValue([1, 2]);
        $configValue->doOnPreUpdate();
        $this->assertEquals('1,2', $configValue->getValue());
        $this->assertEquals(ConfigValue::FIELD_LIST_TYPE, $configValue->getType());

        $obj = new \stdClass();
        $configValue->setValue($obj);
        $configValue->doOnPrePersist();
        $this->assertEquals(serialize($obj), $configValue->getValue());
        $this->assertEquals(ConfigValue::FIELD_SERIALIZED_TYPE, $configValue->getType());

        $configValue->setValue(1);
        $configValue->doOnPrePersist();
        $this->assertEquals(1, $configValue->getValue());
        $this->assertEquals($configValue::FIELD_SCALAR_TYPE, $configValue->getType());

        $configValue->setValue(serialize($obj));
        $configValue->setType($configValue::FIELD_SERIALIZED_TYPE);
        $configValue->doOnPostLoad();
        $this->assertEquals($obj, $configValue->getValue());
        $this->assertNull($configValue->getId());

        $configValue->setValue('1,2');
        $configValue->setType($configValue::FIELD_LIST_TYPE);
        $configValue->doOnPostLoad();
        $this->assertCount(2, $configValue->getValue());
    }
}
