<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\TreeExecutor;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TreeExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TreeExecutor */
    protected $listAction;

    /** @var MockBuilder */
    protected $actionBuilder;

    protected function setUp(): void
    {
        $this->listAction = new class() extends TreeExecutor {
            public function xgetActions(): array
            {
                return $this->actions;
            }
        };
    }

    protected function tearDown(): void
    {
        unset($this->listAction);
    }

    public function testAddAction()
    {
        $expectedActions = [];
        for ($i = 0; $i < 3; $i++) {
            $action = $this->getActionMock();
            $breakOnFailure = (bool)$i % 2;
            $this->listAction->addAction($action, $breakOnFailure);
            $expectedActions[] = [
                'instance' => $action,
                'breakOnFailure' => $breakOnFailure
            ];
        }

        static::assertEquals($expectedActions, $this->listAction->xgetActions());
    }

    public function testExecute()
    {
        $context = [1, 2, 3];

        for ($i = 0; $i < 3; $i++) {
            $action = $this->getActionMock();
            $action->expects(static::once())
                ->method('execute')
                ->with($context);
            $this->listAction->addAction($action);
        }

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->listAction->setDispatcher($dispatcher);

        $this->listAction->execute($context);
    }

    public function testBreakOnFailureEnabledException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TEST');

        $actionError = $this->getExceptionAction();
        /** @var ActionInterface|MockObject $action */
        $action = $this->getMockBuilder(ActionInterface::class)->getMockForAbstractClass();
        $action->expects(static::never())->method('execute');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError, true);
        $this->listAction->addAction($action);
        $this->listAction->execute([]);
    }

    public function testBreakOnFailureDisabledException()
    {
        $actionError = $this->getExceptionAction();
        /** @var ActionInterface|MockObject $action */
        $action = $this->getMockBuilder(ActionInterface::class)->getMockForAbstractClass();
        $action->expects(static::once())->method('execute');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError, false);
        $this->listAction->addAction($action);
        $this->listAction->execute([]);
    }

    public function testBreakOnFailureDisabledLogException()
    {
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        $logger->expects(static::once())
            ->method('log')
            ->with('ALERT', 'TEST');
        $listAction = new TreeExecutor($logger);
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $listAction->setDispatcher($dispatcher);
        $actionError = $this->getExceptionAction();
        $listAction->addAction($actionError, false);
        $listAction->execute([]);
    }

    /**
     * @return MockObject|ActionInterface
     */
    protected function getExceptionAction()
    {
        $action = $this->getMockBuilder(ActionInterface::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->expects(static::once())
            ->method('execute')
            ->willReturnCallback(
                function () {
                    throw new \Exception('TEST');
                }
            );
        return $action;
    }

    /**
     * @return MockObject|ActionInterface
     */
    protected function getActionMock()
    {
        if (!$this->actionBuilder) {
            $this->actionBuilder = $this->getMockBuilder(ActionInterface::class)
                ->onlyMethods(['execute'])
                ->disableOriginalConstructor();
        }

        return $this->actionBuilder->getMockForAbstractClass();
    }

    public function testInitialize()
    {
        static::assertEquals($this->listAction, $this->listAction->initialize([]));
    }
}
