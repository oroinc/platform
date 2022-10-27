<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\AssignUrl;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class AssignUrlTest extends \PHPUnit\Framework\TestCase
{
    /** @var AssignUrl */
    private $action;

    protected function setUp(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::any())
            ->method('generate')
            ->willReturnCallback(function (string $routeName, array $routeParameters = []): string {
                return $this->generateTestUrl($routeName, $routeParameters);
            });

        $this->action = new AssignUrl(new ContextAccessor(), $router);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options)
    {
        $this->action->initialize($options);
        self::assertEquals($options, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function optionsDataProvider(): array
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
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
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
                'exceptionMessage' => 'Attribute parameters is required',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, string $expectedUrl)
    {
        $context = new ItemStub();

        $this->action->initialize($options);
        $this->action->execute($context);

        $urlProperty = $options['attribute'];
        self::assertEquals($expectedUrl, $context->{$urlProperty});
    }

    private function generateTestUrl(string $routeName, array $routeParameters = []): string
    {
        $url = 'url:' . $routeName;
        if ($routeParameters) {
            $url .= ':' . \serialize($routeParameters);
        }

        return $url;
    }
}
