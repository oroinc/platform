<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\FeatureCheckProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeatureCheckProcessorTest extends TestCase
{
    private FeatureCheckProcessor $processor;
    private FeatureChecker&MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new FeatureCheckProcessor($this->featureChecker);
    }

    public function testSkipFailed(): void
    {
        $context = new TransitionContext();
        $context->setError(new \Exception('message'));

        $this->featureChecker->expects($this->never())
            ->method('isResourceEnabled');

        $this->processor->process($context);

        $this->assertTrue($context->hasError());
    }

    public function testProcessCheckOk(): void
    {
        $context = new TransitionContext();
        $context->setWorkflowName('workflow_ok');

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('workflow_ok', FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->processor->process($context);

        $this->assertFalse($context->hasError());
    }

    public function testProcessCheckFalse(): void
    {
        $context = new TransitionContext();
        $context->setWorkflowName('workflow_ok');

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with('workflow_ok', FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(false);

        $this->processor->process($context);

        $this->assertTrue($context->hasError());
        $this->assertInstanceOf(ForbiddenTransitionException::class, $context->getError());
        $this->assertEquals('normalize', $context->getFirstGroup());
    }
}
