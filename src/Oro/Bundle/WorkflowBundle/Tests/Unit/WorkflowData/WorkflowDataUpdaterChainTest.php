<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\WorkflowData;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\WorkflowData\WorkflowDataUpdaterChain;
use Oro\Bundle\WorkflowBundle\WorkflowData\WorkflowDataUpdaterInterface;

class WorkflowDataUpdaterChainTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowDataUpdaterChain */
    protected $chain;

    protected function setUp()
    {
        $this->chain = new WorkflowDataUpdaterChain();
    }

    public function testUpdate()
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowData = new WorkflowData();
        $source = new \stdClass();

        /** @var WorkflowDataUpdaterInterface|\PHPUnit_Framework_MockObject_MockObject $updater1 */
        $updater1 = $this->createMock(WorkflowDataUpdaterInterface::class);
        $updater1->expects($this->once())
            ->method('isApplicable')
            ->with($workflowDefinition, $this->identicalTo($source))
            ->willReturn(false);
        $updater1->expects($this->never())
            ->method('update');

        /** @var WorkflowDataUpdaterInterface|\PHPUnit_Framework_MockObject_MockObject $updater2 */
        $updater2 = $this->createMock(WorkflowDataUpdaterInterface::class);
        $updater2->expects($this->once())
            ->method('isApplicable')
            ->with($workflowDefinition, $this->identicalTo($source))
            ->willReturn(true);
        $updater2->expects($this->once())
            ->method('update')
            ->with($workflowDefinition, $workflowData, $source);

        $this->chain->addUpdater($updater1);
        $this->chain->addUpdater($updater2);

        $this->chain->update($workflowDefinition, $workflowData, $source);
    }
}
