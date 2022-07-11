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

    public function testSaveAndStayRedirectAfterSave()
    {
        $testUrl = 'test\\url\\index.html';

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(Router::ACTION_SAVE_AND_STAY);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($testUrl);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $redirect = $this->router->redirectAfterSave(
            [
                'route'      => 'test_route',
                'parameters' => ['id' => 1],
            ],
            []
        );

        $this->assertEquals($testUrl, $redirect->getTargetUrl());
    }

    public function testSaveAndStayWithAccessGrantedRedirectAfterSave()
    {
        $testUrl = 'test\\url\\index.html';

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(Router::ACTION_SAVE_AND_STAY);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($testUrl);

        $entity = new \stdClass();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($entity))
            ->willReturn(true);

        $redirect = $this->router->redirectAfterSave(
            [
                'route'      => 'test_route',
                'parameters' => ['id' => 1],
            ],
            [],
            $entity
        );

        $this->assertEquals($testUrl, $redirect->getTargetUrl());
    }

    public function testSaveAndStayWithAccessDeniedRedirectAfterSave()
    {
        $testUrl1 = 'test\\url\\index1.html';
        $testUrl2 = 'test\\url\\index2.html';

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(Router::ACTION_SAVE_AND_STAY);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturnCallback(function ($name) use (&$testUrl1, &$testUrl2) {
                if ($name === 'test_route1') {
                    return $testUrl1;
                }
                if ($name === 'test_route2') {
                    return $testUrl2;
                }

                return '';
            });

        $entity = new \stdClass();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($entity))
            ->willReturn(false);

        $redirect = $this->router->redirectAfterSave(
            [
                'route'      => 'test_route1',
                'parameters' => ['id' => 1],
            ],
            [
                'route'      => 'test_route2',
                'parameters' => ['id' => 1],
            ],
            $entity
        );

        $this->assertEquals($testUrl2, $redirect->getTargetUrl());
    }

    public function testSaveAndCloseRedirectAfterSave()
    {
        $testUrl = 'save_and_close.html';

        $this->request->expects($this->once())
            ->method('get')
            ->willReturn(Router::ACTION_SAVE_CLOSE);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($testUrl);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $redirect = $this->router->redirectAfterSave(
            [],
            [
                'route'      => 'test_route',
                'parameters' => ['id' => 1],
            ]
        );

        $this->assertEquals($testUrl, $redirect->getTargetUrl());
    }

    public function testWrongParametersRedirectAfterSave()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->redirectAfterSave([], []);
    }

    public function testRedirectWillBeToTheSamePageIfInputActionIsEmpty()
    {
        $expectedUrl = '/example/view/1';
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($expectedUrl);

        $response = $this->router->redirect([]);
        $this->assertEquals($response->getTargetUrl(), $expectedUrl);
    }

    public function testRedirectFailsWhenInputActionNotString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Request parameter "input_action" must be string, array is given.');

        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(['invalid_value']);

        $this->router->redirect([]);
    }

    public function testRedirectFailsWhenRouteIsEmpty()
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

    public function testRedirectFailsWhenRouteIsNotString()
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

    public function testRedirectFailsWhenRouteParamsIsNotArray()
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
    public function testRedirectWorks(array $expected, array $data)
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
}
