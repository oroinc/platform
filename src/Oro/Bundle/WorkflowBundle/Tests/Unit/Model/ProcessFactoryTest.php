<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Process;
use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;
use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory;
use PHPUnit\Framework\TestCase;

class ProcessFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $actionAssembler = $this->createMock(ActionAssembler::class);
        $processDefinition = $this->createMock(ProcessDefinition::class);
        $conditionFactory = $this->createMock(ExpressionFactory::class);

        $processFactory = new ProcessFactory($actionAssembler, $conditionFactory);
        $this->assertInstanceOf(
            Process::class,
            $processFactory->create($processDefinition)
        );
    }
}
