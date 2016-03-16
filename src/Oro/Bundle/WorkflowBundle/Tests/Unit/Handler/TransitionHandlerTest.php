<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Exception;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;
use Oro\Bundle\WorkflowBundle\Handler\TransitionHandler;

class TransitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $transitionHelper;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $transition;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $workflowItem;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $transitionHandler;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $workflowManager;

    public function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transitionHelper = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Handler\Helper\TransitionHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transitionHandler = new TransitionHandler($this->workflowManager, $this->transitionHelper, $this->logger);
    }

    /**
     * @dataProvider handleErrorProvider
     */
    public function testHandleError($exception, $expectedResponseCode)
    {
        $this->workflowManager
            ->method('transit')
            ->will($this->throwException($exception));

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->transitionHelper
            ->expects($this->once())
            ->method('createCompleteResponse')
            ->with(
                $this->callback(function ($workflowItem) {
                    $this->assertInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem', $workflowItem);
                    return $workflowItem;
                }),
                $this->callback(function ($responseCode) use ($expectedResponseCode) {
                    $this->assertEquals($expectedResponseCode, $responseCode);
                    return $responseCode;
                })
            );

        $this->transitionHandler->handle($this->transition, $this->workflowItem);
    }

    public function handleErrorProvider()
    {
        return [
            '404' => [
                new WorkflowNotFoundException('test_workflow'),
                404,
            ],
            '400' => [
                new InvalidTransitionException,
                400,
            ],
            '403' => [
                new ForbiddenTransitionException,
                403,
            ],
            '500' => [
                new Exception,
                500,
            ],
        ];
    }

    /**
     * @dataProvider transitionMethodsProvider
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
