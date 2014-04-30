<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Action;

use Oro\Bundle\WorkflowBundle\Datagrid\Action\WorkflowActivateAction;

class WorkflowActivateActionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOptions()
    {
        $action = new WorkflowActivateAction();
        $options = $action->getOptions()->toArray();

        $this->assertArrayHasKey('frontend_type', $options);
        $this->assertArrayHasKey('frontend_handle', $options);
        $this->assertEquals('workflow-activate', $options['frontend_type']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
