<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Route;

use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RouterTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $requestQuery;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var Router */
    private $router;

    protected function setUp(): void
    {
        $this->requestQuery = $this->createMock(ParameterBag::class);
        $this->request = $this->createMock(Request::class);
        $this->request->query = $this->requestQuery;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->router = new Router($requestStack, $this->urlGenerator, $this->authorizationChecker);
    }

    public function testRedirectWillBeToTheSamePageIfInputActionIsEmpty(): void
    {
        $expectedUrl = '/example/view/1';
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($expectedUrl);

        $response = $this->router->redirect([]);
        $this->assertEquals($response->getTargetUrl(), $expectedUrl);
    }

    public function testRedirectFailsWhenInputActionNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Request parameter "input_action" must be string, array is given.');

        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(['invalid_value']);

        $this->router->redirect([]);
    }

    public function testRedirectFailsWhenRouteIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot parse route name from request parameter "input_action".'
            . ' Value of key "route" cannot be empty: {"route":""}'
        );

        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => ''], JSON_THROW_ON_ERROR));

        $this->router->redirect([]);
    }

    public function testRedirectFailsWhenRouteIsNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot parse route name from request parameter "input_action".'
            . ' Value of key "route" must be string: {"route":{"foo":"bar"}}'
        );

        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => ['foo' => 'bar']], JSON_THROW_ON_ERROR));

        $this->router->redirect([]);
    }

    public function testRedirectFailsWhenRouteParamsIsNotArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot parse route name from request parameter "input_action".'
            . ' Value of key "params" must be array: {"route":"foo","params":"bar"}'
        );

        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => 'foo', 'params' => 'bar'], JSON_THROW_ON_ERROR));

        $this->router->redirect([]);
    }

    /**
     * @dataProvider redirectDataProvider
     */
    public function testRedirectWorks(array $expected, array $data): void
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode($data['actionParameters'], JSON_THROW_ON_ERROR));

        $this->requestQuery->expects($this->once())
            ->method('all')
            ->willReturn($data['queryParameters']);

        $expectedUrl = 'http://expected.com';
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($expected['route'], $expected['parameters'])
            ->willReturn($expectedUrl);

        $response = $this->router->redirect($data['context']);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function redirectDataProvider(): array
    {
        $expectedId = 42;
        $expectedSecondEntityId = 21;

        return [
            'with query parameters' => [
                'expected' => [
                    'route' => 'test_route',
                    'parameters' => [
                        'testStaticParameter' => User::class,
                        'id' => $expectedId,
                        'testQueryParameter' => 'foo'
                    ]
                ],
                'data' => [
                    'actionParameters' => [
                        'route' => 'test_route',
                        'params' => [
                            'testStaticParameter' => User::class,
                            'id' => '$id'
                        ]
                    ],
                    'context' => $this->getEntityStub($expectedId),
                    'queryParameters' => [
                        'testQueryParameter' => 'foo'
                    ],
                ]
            ],
            'with query parameters overridden by route parameter' => [
                'expected' => [
                    'route' => 'test_route',
                    'parameters' => [
                        'testStaticParameter' => User::class,
                        'id' => $expectedId,
                    ]
                ],
                'data' => [
                    'actionParameters' => [
                        'route' => 'test_route',
                        'params' => [
                            'testStaticParameter' => User::class,
                            'id' => '$id'
                        ]
                    ],
                    'context' => $this->getEntityStub($expectedId),
                    'queryParameters' => [
                        'testStaticParameter' => 'foo'
                    ],
                ]
            ],
            'with dynamic parameters and entity as context' => [
                'expected' => [
                    'route' => 'test_route',
                    'parameters' => [
                        'testStaticParameter' => User::class,
                        'id' => $expectedId
                    ]
                ],
                'data' => [
                    'actionParameters' => [
                        'route' => 'test_route',
                        'params' => [
                            'testStaticParameter' => User::class,
                            'id' => '$id'
                        ]
                    ],
                    'context' => $this->getEntityStub($expectedId),
                    'queryParameters' => [],
                ]
            ],
            'with dynamic parameters and array as context' => [
                'expected' => [
                    'route' => 'test_route',
                    'parameters' => [
                        'testStaticParameter' => User::class,
                        'id' => $expectedId,
                        'secondId' => $expectedSecondEntityId
                    ]
                ],
                'data' => [
                    'actionParameters' => [
                        'route' => 'test_route',
                        'params' => [
                            'testStaticParameter' => User::class,
                            'id' => '$firstEntity.id',
                            'secondId' => '$secondEntity.id'
                        ]
                    ],
                    'context' => [
                        'firstEntity' => $this->getEntityStub($expectedId),
                        'secondEntity' => $this->getEntityStub($expectedSecondEntityId)
                    ],
                    'queryParameters' => [],
                ]
            ],
        ];
    }

    private function getEntityStub(int $id): \stdClass
    {
        $entity = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $entity;
    }

    public function testGetInputActionDataWhenNotString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Request parameter "input_action" must be string, array is given.');

        $this->request
            ->expects(self::once())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(['invalid_value']);

        $this->router->getInputActionData($this->request);
    }

    public function testGetInputActionData(): void
    {
        $inputAction = ['route' => 'test_route', 'parameters' => ['id' => 42]];
        $this->request
            ->expects(self::once())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode($inputAction));

        self::assertEquals($inputAction, $this->router->getInputActionData($this->request));
    }

    public function testGetInputActionDataWithoutRequest(): void
    {
        $inputAction = ['route' => 'test_route', 'parameters' => ['id' => 42]];
        $this->request
            ->expects(self::once())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode($inputAction));

        self::assertEquals($inputAction, $this->router->getInputActionData());
    }
}
