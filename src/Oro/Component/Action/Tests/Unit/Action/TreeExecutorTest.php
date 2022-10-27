<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\TreeExecutor;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TreeExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TreeExecutor */
    private $listAction;

    protected function setUp(): void
    {
        $this->listAction = new TreeExecutor();
    }

    public function testAddAction()
    {
        $expectedActions = [];
        for ($i = 0; $i < 3; $i++) {
            $action = $this->createMock(ActionInterface::class);
            $breakOnFailure = (bool)$i % 2;
            $this->listAction->addAction($action, $breakOnFailure);
            $expectedActions[] = [
                'instance' => $action,
                'breakOnFailure' => $breakOnFailure
            ];
        }

        self::assertEquals($expectedActions, ReflectionUtil::getPropertyValue($this->listAction, 'actions'));
    }

    public function testExecute()
    {
        $context = [1, 2, 3];

        for ($i = 0; $i < 3; $i++) {
            $action = $this->createMock(ActionInterface::class);
            $action->expects(self::once())
                ->method('execute')
                ->with($context);
            $this->listAction->addAction($action);
        }

        $this->listAction->setDispatcher($this->createMock(EventDispatcher::class));

        $this->listAction->execute($context);
    }

    public function testBreakOnFailureEnabledException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('TEST');

        $actionError = $this->createExceptionAction();
        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::never())
            ->method('execute');

        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError);
        $this->listAction->addAction($action);
        $this->listAction->execute([]);
    }

    public function testBreakOnFailureDisabledException()
    {
        $actionError = $this->createExceptionAction();
        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('execute');

        $dispatcher = $this->createMock(EventDispatcher::class);
        $this->listAction->setDispatcher($dispatcher);
        $this->listAction->addAction($actionError, false);
        $this->listAction->addAction($action);
        $this->listAction->execute([]);
    }

    public function testBreakOnFailureDisabledLogException()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with('ALERT', 'TEST');

        $listAction = new TreeExecutor($logger);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $listAction->setDispatcher($dispatcher);
        $actionError = $this->createExceptionAction();
        $listAction->addAction($actionError, false);
        $listAction->execute([]);
    }

    private function createExceptionAction(): ActionInterface
    {
        $action = $this->createMock(ActionInterface::class);
        $action->expects(self::once())
            ->method('execute')
            ->willThrowException(new \Exception('TEST'));

        return $action;
    }

    public function testInitialize()
    {
        self::assertEquals($this->listAction, $this->listAction->initialize([]));
    }
}
