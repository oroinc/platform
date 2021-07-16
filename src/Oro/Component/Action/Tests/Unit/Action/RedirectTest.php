<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\Redirect;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class RedirectTest extends \PHPUnit\Framework\TestCase
{
    private const REDIRECT_PATH = 'redirectUrl';

    /** @var Redirect */
    protected $action;

    /** @var MockObject|RouterInterface */
    protected $router;

    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->router->method('generate')->willReturnCallback([$this, 'generateTestUrl']);

        $this->action = new class(new ContextAccessor(), $this->router, self::REDIRECT_PATH) extends Redirect {
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

    public function optionsDataProvider(): array
    {
        return [
            'route' => [
                'options' => [
                    'route' => 'test_route_name'
                ],
                'expectedUrl' => $this->generateTestUrl('test_route_name'),
            ],
            'route with parameters' => [
                'options' => [
                    'route' => 'test_route_name',
                    'route_parameters' => ['id' => 1],
                ],
                'expectedUrl' => $this->generateTestUrl('test_route_name', ['id' => 1]),
            ],
            'plain url' => [
                'options' => [
                    'url' => 'http://some.host/path'
                ],
                'expectedUrl' => 'http://some.host/path'
            ],
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

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no name' => [
                'options' => [],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Either url or route parameter must be specified',
            ],
            'invalid route parameters' => [
                'options' => [
                    'route' => 'test_route_name',
                    'route_parameters' => 'stringData',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Route parameters must be an array',
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

        $urlProperty = self::REDIRECT_PATH;
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
