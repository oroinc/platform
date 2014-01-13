<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessDefinition
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ProcessDefinition();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @param mixed $propertyName
     * @param mixed $testValue
     * @param mixed $defaultValue
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity($propertyName, $testValue, $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = (is_bool($testValue) ? 'is' : 'get') . ucfirst($propertyName);

        $this->assertSame($defaultValue, $this->entity->$getter());
        $this->assertSame($this->entity, $this->entity->$setter($testValue));
        $this->assertSame($testValue, $this->entity->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return array(
            'name' => array('name', 'test'),
            'label' => array('label', 'Test Definition'),
            'enabled' => array('enabled', false, true),
            'actionsConfiguration' => array('actionsConfiguration', array('my' => 'configuration')),
            'relatedEntity' => array('relatedEntity', 'My\Entity'),
            'executionOrder' => array('executionOrder', 42, 0),
            'executionRequired' => array('executionRequired', true, false),
            'createdAt' => array('createdAt', new \DateTime()),
            'updatedAt' => array('updatedAt', new \DateTime()),
        );
    }

    public function testImport()
    {
        $importedEntity = new ProcessDefinition();
        $importedEntity->setName('my_name')
            ->setLabel('My Label')
            ->setEnabled(false)
            ->setRelatedEntity('My/Entity')
            ->setExecutionOrder(25)
            ->setExecutionRequired(true)
            ->setActionsConfiguration(array('key' => 'value'));

        $this->assertNotEquals($importedEntity->getName(), $this->entity->getName());
        $this->assertNotEquals($importedEntity->getLabel(), $this->entity->getLabel());
        $this->assertNotEquals($importedEntity->getRelatedEntity(), $this->entity->getRelatedEntity());
        $this->assertNotEquals($importedEntity->getExecutionOrder(), $this->entity->getExecutionOrder());
        $this->assertNotEquals($importedEntity->isExecutionRequired(), $this->entity->isExecutionRequired());
        $this->assertNotEquals($importedEntity->getActionsConfiguration(), $this->entity->getActionsConfiguration());
        $this->assertTrue($this->entity->isEnabled());

        $this->entity->import($importedEntity);

        $this->assertEquals($importedEntity->getName(), $this->entity->getName());
        $this->assertEquals($importedEntity->getLabel(), $this->entity->getLabel());
        $this->assertEquals($importedEntity->getRelatedEntity(), $this->entity->getRelatedEntity());
        $this->assertEquals($importedEntity->getExecutionOrder(), $this->entity->getExecutionOrder());
        $this->assertEquals($importedEntity->isExecutionRequired(), $this->entity->isExecutionRequired());
        $this->assertEquals($importedEntity->getActionsConfiguration(), $this->entity->getActionsConfiguration());
        $this->assertTrue($this->entity->isEnabled()); // enabled must not be changed
    }
}
