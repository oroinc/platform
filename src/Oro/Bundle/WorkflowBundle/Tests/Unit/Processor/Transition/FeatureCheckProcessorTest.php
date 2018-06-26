<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\FeatureCheckProcessor;

class FeatureCheckProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureCheckProcessor */
    protected $processor;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new FeatureCheckProcessor($this->featureChecker);
    }

    public function testSkipFailed()
    {
        $context = new TransitionContext();
        $context->setError(new \Exception('message'));

        $this->featureChecker->expects($this->never())->method('isResourceEnabled');

        $this->processor->process($context);

        $this->assertTrue($context->hasError());
    }

    public function testProcessCheckOk()
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

    public function testProcessCheckFalse()
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
