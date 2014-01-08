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
            'name'    => array('name', 'test'),
            'enabled' => array('enabled', false, true),
            'configuration' => array('configuration', serialize(array('my' => 'configuration'))),
            'relatedEntity' => array('relatedEntity', 'My\Entity'),
            'executionOrder' => array('executionOrder', 42, 0),
            'executionRequired' => array('executionRequired', true, false),
            'createdAt' => array('createdAt', new \DateTime()),
            'updatedAt' => array('updatedAt', new \DateTime()),
        );
    }
}
