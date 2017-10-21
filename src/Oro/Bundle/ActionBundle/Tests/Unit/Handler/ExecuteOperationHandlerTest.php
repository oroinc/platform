<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Handler;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Action\Exception\InvalidConfigurationException;

use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Handler\ExecuteOperationHandler;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;

class ExecuteOperationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var FormProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $formProvider;

    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var Operation|\PHPUnit_Framework_MockObject_MockObject */
    protected $operation;

    /** @var ActionData|\PHPUnit_Framework_MockObject_MockObject */
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
            ->method('isValid')
            ->willReturn(true);

        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->handler->process($this->operation);

        $this->assertTrue($result->isSuccess());
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

        $this->operation
            ->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->handler->process($this->operation);

        $this->assertTrue($result->isSuccess());
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
            ->method('isValid')
            ->willReturn(false);

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
            ->method('isValid')
            ->willReturn(true);

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
            ->method('submit')
            ->willThrowException(new AlreadySubmittedException('form already submitted'));

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
            ->method('isValid')
            ->willReturn(true);

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
