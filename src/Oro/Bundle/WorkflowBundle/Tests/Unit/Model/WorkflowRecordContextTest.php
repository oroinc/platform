<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\WorkflowRecordContext;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub\EntityStub;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;

class WorkflowRecordContextTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructionValues()
    {
        $entity = new StubEntity(42);
        $recordContext = new WorkflowRecordContext($entity);

        $this->assertSame($entity, $recordContext->getEntity());
    }

    public function testInvalidEntityConstruction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Instance of entity object is required. Got `string` instead.');

        new WorkflowRecordContext(EntityStub::class);
    }
}
