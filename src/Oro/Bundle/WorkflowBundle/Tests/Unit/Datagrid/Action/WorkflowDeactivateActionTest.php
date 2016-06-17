<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Action;

use Oro\Bundle\WorkflowBundle\Datagrid\Action\WorkflowDeactivateAction;

class WorkflowDeactivateActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOptions()
    {
        $action = new WorkflowDeactivateAction();
        $options = $action->getOptions()->toArray();

        $this->assertArrayHasKey('frontend_type', $options);
        $this->assertArrayHasKey('frontend_handle', $options);
        $this->assertEquals('workflow-deactivate', $options['frontend_type']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
