<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;

class WorkflowRecordContextTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEntity(): void
    {
        $entity = new \stdClass();
        $context = new WorkflowRecordContext($entity);
        $this->assertSame($entity, $context->getEntity());
    }
}
