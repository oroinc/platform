<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private Config $object;

    #[\Override]
    protected function setUp(): void
    {
        $this->object = new Config();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->object->getId());
    }

    public function testEntity(): void
    {
        $object = $this->object;
        $entity = 'Oro\Entity';

        $this->assertEmpty($object->getScopedEntity());

        $object->setScopedEntity($entity);

        $this->assertEquals($entity, $object->getScopedEntity());
        $this->assertInstanceOf(ArrayCollection::class, $object->getValues());
    }

    public function testRecordId(): void
    {
        $object = $this->object;
        $id = 5;

        $this->assertEmpty($object->getRecordId());

        $object->setRecordId($id);

        $this->assertEquals($id, $object->getRecordId());
    }

    /**
     * Test getOrCreateValue
     */
    public function testGetOrCreateValue(): void
    {
        $object = $this->object;

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
}
