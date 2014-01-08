<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;

class ProcessJobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessJob
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ProcessJob();
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
            'job' => array('job', new Job('my_command')),
            'processTrigger' => array('processTrigger', new ProcessTrigger()),
            'entityHash' => array('entityHash', 'My\Entity' . serialize(array('id' => 1))),
            'serializedData' => array('serializedData', serialize(array('some' => 'data'))),
        );
    }
}
