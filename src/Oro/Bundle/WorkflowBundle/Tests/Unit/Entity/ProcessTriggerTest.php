<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessTriggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessTrigger
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ProcessTrigger();
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
            'event' => array('event', 'update'),
            'field' => array('field', 'status'),
            'timeShift' => array('timeShift', time()),
            'definition' => array('definition', new ProcessDefinition()),
            'createdAt' => array('createdAt', new \DateTime()),
            'updatedAt' => array('updatedAt', new \DateTime()),
        );
    }
}
