<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\Redirect;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;

class RedirectTest extends \PHPUnit\Framework\TestCase
{
    private const REDIRECT_PATH = 'redirectUrl';

    /** @var Redirect */
    private $action;

    protected function setUp(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::any())
            ->method('generate')
            ->willReturnCallback(function (string $routeName, array $routeParameters = []): string {
                return $this->generateTestUrl($routeName, $routeParameters);
            });

        $this->action = new Redirect(new ContextAccessor(), $router, self::REDIRECT_PATH);
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
     * @dataProvider optionsDataProvider
     */
    public function testExecute(array $options, string $expectedUrl)
    {
        $context = new ItemStub();

        $this->action->initialize($options);
        $this->action->execute($context);

        $urlProperty = self::REDIRECT_PATH;
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
