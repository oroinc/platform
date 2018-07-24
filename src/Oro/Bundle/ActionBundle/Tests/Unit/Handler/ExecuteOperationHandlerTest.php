<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Handler\ExecuteOperationHandler;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Component\Action\Exception\InvalidConfigurationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class ExecuteOperationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var FormProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $formProvider;

    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var Operation|\PHPUnit\Framework\MockObject\MockObject */
    protected $operation;

    /** @var ActionData|\PHPUnit\Framework\MockObject\MockObject */
    protected $actionData;

    /** @var ExecuteOperationHandler */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->formProvider = $this->createMock(FormProvider::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->actionData = new ActionData();
        $this->operation = $this->createMock(Operation::class);

        $this->contextHelper
            ->expects($this->once())
            ->method('getActionData')
            ->willReturn($this->actionData);
        $this->operation
            ->expects($this->any())
            ->method('getName')
            ->willReturn('test_operation');


        $this->handler = new ExecuteOperationHandler(
            $this->requestStack,
            $this->formProvider,
            $this->contextHelper,
            $this->logger
        );
    }

    public function testProcessSuccess()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $executionForm = $this->createMock(FormInterface::class);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->with($this->operation, $this->actionData)
            ->willReturn($executionForm);
        $executionForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $executionForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $definition = new OperationDefinition();
        $definition->setPageReload(true);
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->handler->process($this->operation);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($definition->isPageReload(), $result->isPageReload());
        $this->assertEmpty($result->getValidationErrors());
    }

    public function testProcessSuccessWithoutRequest()
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->formProvider
            ->expects($this->never())
            ->method('getOperationExecutionForm');

        $definition = new OperationDefinition();
        $definition->setPageReload(true);
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->handler->process($this->operation);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($definition->isPageReload(), $result->isPageReload());
        $this->assertEmpty($result->getValidationErrors());
    }

    public function testProcessInvalidForm()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $executionForm = $this->createMock(FormInterface::class);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->with($this->operation, $this->actionData)
            ->willReturn($executionForm);
        $executionForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $executionForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $definition = new OperationDefinition();
        $definition->setPageReload(true);
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Execution of operation "{operation}" failed')
            ->willReturnCallback(function ($message, array $context) {
                self::assertEquals('test_operation', $context['operation']);
                self::assertInstanceOf(ForbiddenOperationException::class, $context['exception']);
            });

        $result = $this->handler->process($this->operation);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(true, $result->isPageReload());
        $this->assertEquals(
            sprintf('Operation "%s" execution is forbidden!', $this->operation->getName()),
            $result->getExceptionMessage()
        );
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getCode());
    }

    public function testProcessOperationNotAvailable()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $executionForm = $this->createMock(FormInterface::class);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->with($this->operation, $this->actionData)
            ->willReturn($executionForm);
        $executionForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $executionForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $definition = new OperationDefinition();
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturnCallback(function (ActionData $data, Collection $errors) {
                $errors->add(['message' => 'some error']);

                return false;
            });

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Execution of operation "{operation}" failed')
            ->willReturnCallback(function ($message, array $context) {
                self::assertEquals('test_operation', $context['operation']);
                self::assertInstanceOf(ForbiddenOperationException::class, $context['exception']);
                self::assertEquals(['some error'], $context['validationErrors']);
            });

        $result = $this->handler->process($this->operation);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(
            sprintf('Operation "%s" execution is forbidden!', $this->operation->getName()),
            $result->getExceptionMessage()
        );
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getCode());
        $this->assertEquals(
            [
                ['message' => 'some error']
            ],
            $result->getValidationErrors()->toArray()
        );
    }

    public function testProcessFormNotConfigured()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $definition = new OperationDefinition();
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->willThrowException(new InvalidConfigurationException('execution form error'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Execution of operation "{operation}" failed')
            ->willReturnCallback(function ($message, array $context) {
                self::assertEquals('test_operation', $context['operation']);
                self::assertInstanceOf(InvalidConfigurationException::class, $context['exception']);
            });

        $result = $this->handler->process($this->operation);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $result->getCode());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('execution form error', $result->getExceptionMessage());
    }

    public function testProcessAlreadySubmitted()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $executionForm = $this->createMock(FormInterface::class);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->with($this->operation, $this->actionData)
            ->willReturn($executionForm);
        $executionForm
            ->expects($this->once())
            ->method('handleRequest')
            ->willThrowException(new AlreadySubmittedException('form already submitted'));

        $definition = new OperationDefinition();
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Execution of operation "{operation}" failed')
            ->willReturnCallback(function ($message, array $context) {
                self::assertEquals('test_operation', $context['operation']);
                self::assertInstanceOf(AlreadySubmittedException::class, $context['exception']);
            });

        $result = $this->handler->process($this->operation);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getCode());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('form already submitted', $result->getExceptionMessage());
    }

    public function testProcessExecuteException()
    {
        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $executionForm = $this->createMock(FormInterface::class);
        $this->formProvider
            ->expects($this->once())
            ->method('getOperationExecutionForm')
            ->with($this->operation, $this->actionData)
            ->willReturn($executionForm);
        $executionForm
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $executionForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $definition = new OperationDefinition();
        $this->operation
            ->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);
        $this->operation
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new ForbiddenOperationException('operation execution error'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Execution of operation "{operation}" failed')
            ->willReturnCallback(function ($message, array $context) {
                self::assertEquals('test_operation', $context['operation']);
                self::assertInstanceOf(ForbiddenOperationException::class, $context['exception']);
            });

        $result = $this->handler->process($this->operation);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getCode());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('operation execution error', $result->getExceptionMessage());
    }

    protected function tearDown()
    {
        unset(
            $this->handler,
            $this->actionData,
            $this->operation,
            $this->contextHelper,
            $this->formProvider,
            $this->requestStack
        );
    }
}
