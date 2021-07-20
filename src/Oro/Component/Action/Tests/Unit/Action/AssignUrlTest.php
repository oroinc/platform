<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignUrl;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class AssignUrlTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|RouterInterface */
    protected $router;

    /** @var AssignUrl */
    protected $action;

    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->router->method('generate')->willReturnCallback([$this, 'generateTestUrl']);

        $this->action = new class(new ContextAccessor(), $this->router) extends AssignUrl {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->router, $this->action);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);
        static::assertEquals($options, $this->action->xgetOptions());
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'route' => [
                'options' => [
                    'route' => 'test_route_name',
                    'attribute' => 'test'
                ],
                'expectedUrl' => $this->generateTestUrl('test_route_name'),
            ],
            'route with parameters' => [
                'options' => [
                    'route' => 'test_route_name',
                    'route_parameters' => ['id' => 1],
                    'attribute' => 'test'
                ],
                'expectedUrl' => $this->generateTestUrl('test_route_name', ['id' => 1]),
            ]
        ];
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            'no name' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Route parameter must be specified',
            ],
            'invalid route parameters' => [
                'options' => [
                    'route' => 'test_route_name',
                    'route_parameters' => 'stringData',
                    'attribute' => 'test'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Route parameters must be an array',
            ],
            'no attribute' => [
                'options' => [
                    'route' => 'test_route_name'
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Attribiute parameters is required',
            ],
        ];
    }

    /**
     * @param array $options
     * @param string $expectedUrl
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, $expectedUrl)
    {
        $context = new ItemStub();

        $this->action->initialize($options);
        $this->action->execute($context);

        $urlProperty = $options['attribute'];
        static::assertEquals($expectedUrl, $context->$urlProperty);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @return string
     */
    public function generateTestUrl($routeName, array $routeParameters = [])
    {
        $url = 'url:' . $routeName;
        if ($routeParameters) {
            $url .= ':' . \serialize($routeParameters);
        }

        return $url;
    }
}
