<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CallServiceMethod;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Tests\Unit\Action\Stub\StubStorage;
use Oro\Component\Action\Tests\Unit\Action\Stub\TestService;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class CallServiceMethodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    private $container;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CallServiceMethod
     */
    private $action;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->action = new CallServiceMethod(new ContextAccessor(), $this->container);
        $this->action->setDispatcher($this->eventDispatcher);
    }

    protected function tearDown()
    {
        unset($this->action, $this->eventDispatcher, $this->container);
    }

    public function testInitialize()
    {
        $options = [
            'service' => 'test_service',
            'method' => 'testMethod',
            'method_parameters' => ['param' => 'value'],
            'attribute' => 'test'
        ];

        $this->assertInstanceOf(
            'Oro\Component\Action\Action\ActionInterface',
            $this->action->initialize($options)
        );

        $this->assertAttributeEquals($options, 'options', $this->action);
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

        $this->container->expects(static::once())
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

        $this->assertEquals(
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

        $this->assertEquals(['param' => 'value'], $context->getValues());
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

        $this->assertEquals(
            ['param' => 'value', 'service' => $service],
            $context->getValues()
        );
    }

    /**
     * @param string $serviceName
     */
    private function setContainerServiceExpectations($serviceName)
    {
        $this->container->expects(static::once())
            ->method('has')
            ->with($serviceName)
            ->willReturn(true);
        $this->container->expects(static::once())
            ->method('get')
            ->with($serviceName)
            ->willReturn(new TestService());
    }
}
