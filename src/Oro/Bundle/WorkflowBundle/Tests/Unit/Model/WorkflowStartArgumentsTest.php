<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;

class WorkflowStartArgumentsTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorSetsProperValues()
    {
        $workflowName = 'workflow_name';
        $entity = new EntityStub(42);
        $data = [1, 2, 3];
        $transition = 'workflow_start_transition_name';

        $workflowStartArguments = new WorkflowStartArguments(
            $workflowName,
            $entity,
            $data,
            $transition
        );

        $this->assertEquals($workflowName, $workflowStartArguments->getWorkflowName());
        $this->assertSame($entity, $workflowStartArguments->getEntity());
        $this->assertEquals($data, $workflowStartArguments->getData());
        $this->assertEquals($transition, $workflowStartArguments->getTransition());
    }

    public function testConstructorOptionalValues()
    {
        $workflowName = 'workflow_name';
        $entity = new EntityStub(42);

        $workflowStartArguments = new WorkflowStartArguments($workflowName, $entity);

        $this->assertEquals([], $workflowStartArguments->getData());
        $this->assertNull($workflowStartArguments->getTransition());
    }
}
