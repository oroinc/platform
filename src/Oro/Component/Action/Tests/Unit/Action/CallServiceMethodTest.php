<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\CallServiceMethod;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\Action\Tests\Unit\Action\Stub\TestService;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CallServiceMethodTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var CallServiceMethod */
    private $action;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->action = new CallServiceMethod(new ContextAccessor(), $this->container);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testInitialize()
    {
        $options = [
            'service' => 'test_service',
            'method' => 'testMethod',
            'method_parameters' => ['param' => 'value'],
            'attribute' => 'test'
        ];

        self::assertInstanceOf(ActionInterface::class, $this->action->initialize($options));
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function testInitializeNoServiceException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Service name parameter is required');

        $this->action->initialize([]);
    }

    public function testInitializeNoMethodException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Method name parameter is required');

        $this->action->initialize([
            'service' => 'service'
        ]);
    }

    public function testExecuteActionUndefinedServiceException()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Undefined service with name "test_service"');

        $service = 'test_service';
        $options = [
            'service' => $service,
            'method' => 'testMethod',
        ];

        $this->container->expects(self::once())
            ->method('has')
            ->with($service)
            ->willReturn(false);

        $this->action->initialize($options);
        $this->action->execute('');
    }

    public function testExecuteActionMethodNotExists()
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Could not found public method "noMethod" in service "test_service"');

        $service = 'test_service';
        $options = [
            'service' => $service,
            'method' => 'noMethod',
        ];

        $this->setContainerServiceExpectations($service);

        $this->action->initialize($options);
        $this->action->execute('');
    }

    public function testExecuteActionWithAttribute()
    {
        $service = 'test_service';
        $options = [
            'service' => $service,
            'method' => 'testMethod',
            'method_parameters' => [new PropertyPath('param')],
            'attribute' => 'test'
        ];

        $this->setContainerServiceExpectations($service);

        $context = new StubStorage(['param' => 'value']);

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(
            ['param' => 'value', 'test' => TestService::TEST_METHOD_RESULT . 'value'],
            $context->getValues()
        );
    }

    public function testExecuteActionWithoutAttribute()
    {
        $service = 'test_service';
        $options = [
            'service' => $service,
            'method' => 'testMethod',
            'method_parameters' => [new PropertyPath('param')],
        ];

        $this->setContainerServiceExpectations($service);

        $context = new StubStorage(['param' => 'value']);

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(['param' => 'value'], $context->getValues());
    }

    public function testExecuteActionPropertyPathService()
    {
        $service = 'test_service';
        $options = [
            'service' => new PropertyPath('service'),
            'method' => 'testMethod',
            'method_parameters' => [new PropertyPath('param')],
        ];

        $this->setContainerServiceExpectations($service);

        $context = new StubStorage(['param' => 'value', 'service' => $service]);

        $this->action->initialize($options);
        $this->action->execute($context);

        self::assertEquals(
            ['param' => 'value', 'service' => $service],
            $context->getValues()
        );
    }

    private function setContainerServiceExpectations(string $serviceName): void
    {
        $this->container->expects(self::once())
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with($serviceName)
            ->willReturn(new TestService());
    }
}
