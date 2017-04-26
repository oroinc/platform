<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Psr\Log\LoggerInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper;
use Oro\Bundle\WorkflowBundle\Handler\TransitionHandler;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $transitionHelper;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    private $transition;

    /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowItem;

    /** @var TransitionHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $transitionHandler;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowManager;

    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transitionHelper = $this->getMockBuilder(TransitionHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transition = $this->getMockBuilder(Transition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowItem = $this->getMockBuilder(WorkflowItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transitionHandler = new TransitionHandler(
            $this->workflowManager,
            $this->transitionHelper,
            $this->logger
        );
    }

    /**
     * @dataProvider handleErrorProvider
     *
     * @param \Exception $exception
     * @param int $expectedResponseCode
     * @param string $expectedResponseMessage
     */
    public function testHandleError($exception, $expectedResponseCode, $expectedResponseMessage)
    {
        $this->workflowManager
            ->method('transit')
            ->will($this->throwException($exception));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->willReturnCallback(
                function ($message, array $context) {
                    $this->assertEquals('[TransitionHandler] Could not perform transition.', $message);
                    $this->assertArrayHasKey('exception', $context);
                    $this->assertInstanceOf(\Exception::class, $context['exception']);
                    $this->assertArrayHasKey('transition', $context);
                    $this->assertInstanceOf(Transition::class, $context['transition']);
                    $this->assertArrayHasKey('workflowItem', $context);
                    $this->assertInstanceOf(WorkflowItem::class, $context['workflowItem']);
                }
            );

        $this->transitionHelper
            ->expects($this->once())
            ->method('createCompleteResponse')
            ->with(
                $this->isInstanceOf(WorkflowItem::class),
                $this->equalTo($expectedResponseCode),
                $this->equalTo($expectedResponseMessage)
            );

        $this->transitionHandler->handle($this->transition, $this->workflowItem);
    }

    /**
     * @return array
     */
    public function handleErrorProvider()
    {
        return [
            '404' => [
                new WorkflowNotFoundException('test_workflow'),
                404,
                'Workflow "test_workflow" not found',
            ],
            '400' => [
                new InvalidTransitionException('400 message'),
                400,
                '400 message',
            ],
            '403' => [
                new ForbiddenTransitionException('403 message'),
                403,
                '403 message',
            ],
            '500' => [
                new \Exception('500 message'),
                500,
                '500 message',
            ],
        ];
    }

    /**
     * @dataProvider transitionMethodsProvider
     * @param string $dialogTemplate
     * @param string $pageTemplate
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $managerExpect
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $loggerExpect
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $helperExpect
     */
    public function testHandle($dialogTemplate, $pageTemplate, $managerExpect, $loggerExpect, $helperExpect)
    {
        $this->workflowManager
            ->expects($managerExpect)
            ->method('transit');

        $this->logger
            ->expects($loggerExpect)
            ->method('error');

        $this->transitionHelper
            ->expects($helperExpect)
            ->method('createCompleteResponse');

        $this->transition
            ->method('getDialogTemplate')
            ->willReturn($dialogTemplate);

        $this->transition
            ->method('getPageTemplate')
            ->willReturn($pageTemplate);

        $this->transitionHandler->handle($this->transition, $this->workflowItem);
    }

    /**
     * @return array
     */
    public function transitionMethodsProvider()
    {
        return [
            'dialogTemplate' => [
                'template',
                null,
                $this->never(),
                $this->never(),
                $this->never(),
            ],
            'pageTemplate' => [
                null,
                'template',
                $this->never(),
                $this->never(),
                $this->never(),
            ],
            'page and dialog template' => [
                'template',
                'template',
                $this->never(),
                $this->never(),
                $this->never(),
            ],
            'no templates' => [
                null,
                null,
                $this->once(),
                $this->never(),
                $this->once(),
            ],
        ];
    }
}
