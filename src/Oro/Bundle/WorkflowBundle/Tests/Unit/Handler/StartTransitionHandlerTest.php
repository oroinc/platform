<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;
use Oro\Bundle\WorkflowBundle\Handler\StartTransitionHandler;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

class StartTransitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

   /** @var WorkflowAwareSerializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

   /** @var TransitionHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionHelper;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    protected $featureChecker;

    /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflow;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    protected $transition;

    /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowItem;

    /** @var StartTransitionHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionHandler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->serializer = $this->getMockBuilder(WorkflowAwareSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transitionHelper = $this->getMockBuilder(TransitionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transition = $this->getMockBuilder(Transition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transitionHandler = new StartTransitionHandler(
            $this->workflowManager,
            $this->serializer,
            $this->transitionHelper,
            $this->featureChecker
        );
    }

    public function testHandleErrorWithDisabledFeature()
    {
        $entity = (object)[];

        $this->workflow->expects($this->once())->method('getName')->willReturn('workflow');
        $this->featureChecker->expects($this->once())->method('isResourceEnabled')->willReturn(false);
        $this->workflowManager->expects($this->never())->method('startWorkflow');

        $this->transitionHelper->expects($this->once())
            ->method('createCompleteResponse')
            ->with(null, $this->equalTo(403), $this->equalTo(null));

        $this->transitionHandler->handle($this->workflow, $this->transition, [], $entity);
    }

    /**
     * @param \Exception $exception
     * @param int $expectedCode
     * @param string $expectedMessage
     *
     * @dataProvider handleErrorProvider
     */
    public function testHandleError($exception, $expectedCode, $expectedMessage)
    {
        $entity = (object)[];

        $this->workflow->expects($this->once())->method('getName')->willReturn('workflow');
        $this->featureChecker->expects($this->once())->method('isResourceEnabled')->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with('workflow', $entity, $this->transition, [])
            ->will($this->throwException($exception));

        $this->transitionHelper->expects($this->once())
            ->method('createCompleteResponse')
            ->with(null, $this->equalTo($expectedCode), $this->equalTo($expectedMessage));

        $this->transitionHandler->handle($this->workflow, $this->transition, [], $entity);
    }

    /**
     * @return array
     */
    public function handleErrorProvider()
    {
        return [
            'http exception' => [
                new HttpException(500, 'message'),
                500,
                'message',
            ],
            'workflow not found exception' => [
                new WorkflowNotFoundException('workflow1'),
                404,
                'Workflow "workflow1" not found',
            ],
            'unknown attribute exception' => [
                new UnknownAttributeException('message'),
                400,
                'message',
            ],
            'invalid transition exception' => [
                new InvalidTransitionException('message'),
                400,
                'message',
            ],
            'forbidden transition exception' => [
                new ForbiddenTransitionException('message'),
                403,
                'message',
            ],
            'unknown exception' => [
                new \Exception('message'),
                500,
                'message',
            ],
        ];
    }
}
