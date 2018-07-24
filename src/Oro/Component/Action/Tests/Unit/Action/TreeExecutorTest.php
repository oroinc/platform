<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\TreeExecutor;

class TreeExecutorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TreeExecutor
     */
    protected $listAction;

    /**
     * @var \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected $actionBuilder;

    protected function setUp()
    {
        $this->listAction = new TreeExecutor();
    }

    protected function tearDown()
    {
        unset($this->listAction);
    }

    public function testAddAction()
    {
        $expectedActions = array();
        for ($i = 0; $i < 3; $i++) {
            $action = $this->getActionMock();
            $breakOnFailure = (bool)$i % 2;
            $this->listAction->addAction($action, $breakOnFailure);
            $expectedActions[] = array(
                'instance' => $action,
                'breakOnFailure' => $breakOnFailure
            );
        }

        $this->assertAttributeEquals($expectedActions, 'actions', $this->listAction);
    }

    public function testExecute()
    {
        $context = array(1, 2, 3);

        for ($i = 0; $i < 3; $i++) {
            $action = $this->getActionMock();
            $action->expects($this->once())
                ->method('execute')
                ->with($context);
            $this->listAction->addAction($action);
        }

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listAction->setDispatcher($dispatcher);

        $this->listAction->execute($context);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage TEST
     */
    public function testBreakOnFailureEnabledException()
    {
        $actionError = $this->getExceptionAction();
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->getMockForAbstractClass();
        $action->expects($this->never())
            ->method('execute');

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError, true);
        $this->listAction->addAction($action);
        $this->listAction->execute(array());
    }

    public function testBreakOnFailureDisabledException()
    {
        $actionError = $this->getExceptionAction();
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->getMockForAbstractClass();
        $action->expects($this->once())
            ->method('execute');

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError, false);
        $this->listAction->addAction($action);
        $this->listAction->execute(array());
    }

    public function testBreakOnFailureDisabledLogException()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMockForAbstractClass();
        $logger->expects($this->once())
            ->method('log')
            ->with('ALERT', 'TEST');
        $listAction = new TreeExecutor($logger);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $listAction->setDispatcher($dispatcher);
        $actionError = $this->getExceptionAction();
        $listAction->addAction($actionError, false);
        $listAction->execute(array());
    }

    protected function getExceptionAction()
    {
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->setMethods(array('execute'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->expects($this->once())
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function () {
                        throw new \Exception('TEST');
                    }
                )
            );
        return $action;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getActionMock()
    {
        if (!$this->actionBuilder) {
            $this->actionBuilder =
                $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
                    ->setMethods(array('execute'))
                    ->disableOriginalConstructor();
        }

        return $this->actionBuilder->getMockForAbstractClass();
    }

    public function testInitialize()
    {
        $this->assertEquals($this->listAction, $this->listAction->initialize(array()));
    }
}
