<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Action;

use Oro\Bundle\WorkflowBundle\Datagrid\Action\WorkflowActivateAction;
use PHPUnit\Framework\TestCase;

class WorkflowActivateActionTest extends TestCase
{
    public function testGetOptions(): void
    {
        $action = new WorkflowActivateAction();
        $options = $action->getOptions()->toArray();

        $this->assertArrayHasKey('frontend_type', $options);
        $this->assertArrayHasKey('frontend_handle', $options);
        $this->assertEquals('workflow-activate', $options['frontend_type']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
