<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

abstract class AbstractTransitionTriggerTestCase extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var AbstractTransitionTrigger
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = $this->getEntity();
    }

    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->entity, [
            ['id', 1],
            ['queued', false, true],
            ['entityClass', 'test_entity'],
            ['workflowDefinition', new WorkflowDefinition()],
        ]);
    }

    /**
     * @return AbstractTransitionTrigger
     */
    abstract protected function getEntity();
}
