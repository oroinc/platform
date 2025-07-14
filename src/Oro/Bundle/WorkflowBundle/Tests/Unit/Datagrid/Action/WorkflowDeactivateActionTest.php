<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid\Action;

use Oro\Bundle\WorkflowBundle\Datagrid\Action\WorkflowDeactivateAction;
use PHPUnit\Framework\TestCase;

class WorkflowDeactivateActionTest extends TestCase
{
    public function testGetOptions(): void
    {
        $action = new WorkflowDeactivateAction();
        $options = $action->getOptions()->toArray();

        $this->assertArrayHasKey('frontend_type', $options);
        $this->assertArrayHasKey('frontend_handle', $options);
        $this->assertEquals('workflow-deactivate', $options['frontend_type']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
