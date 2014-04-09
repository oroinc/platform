<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

class EntityConfigModelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'Oro\Bundle\TestBundle\Entity\TestEntity';
    const TEST_MODULE = 'OroTestBundle';
    const TEST_ENTITY = 'TestEntity';

    /**
     * @var EntityConfigModel
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new EntityConfigModel();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testConstruct()
    {
        $emptyCollection = new ArrayCollection();

        $this->entity = new EntityConfigModel(self::TEST_CLASS);

        $this->assertEquals(self::TEST_CLASS, $this->entity->getClassName());
        $this->assertEquals(self::TEST_MODULE, $this->entity->getModuleName());
        $this->assertEquals(self::TEST_ENTITY, $this->entity->getEntityName());
        $this->assertEquals(ConfigModelManager::MODE_DEFAULT, $this->entity->getMode());
        $this->assertEquals($emptyCollection, $this->entity->getFields());
        $this->assertEquals($emptyCollection, $this->entity->getIndexedValues());
    }

    public function testSetClassName()
    {
        $this->assertEmpty($this->entity->getClassName());
        $this->assertEmpty($this->entity->getModuleName());
        $this->assertEmpty($this->entity->getEntityName());

        $this->entity->setClassName(self::TEST_CLASS);

        $this->assertEquals(self::TEST_CLASS, $this->entity->getClassName());
        $this->assertEquals(self::TEST_MODULE, $this->entity->getModuleName());
        $this->assertEquals(self::TEST_ENTITY, $this->entity->getEntityName());
    }
}
