<?php

namespace Oro\Bundle\UIBundle\Tests\Route;

use Oro\Bundle\UIBundle\Route\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $symfonyRouter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /**
     * @var Router
     */
    protected $router;

    protected function setUp()
    {
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');

        $this->symfonyRouter = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router = new Router($this->request, $this->symfonyRouter, $this->securityFacade);
    }

    public function testSaveAndStayRedirectAfterSave()
    {
        $testUrl = 'test\\url\\index.html';

        $this->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Router::ACTION_SAVE_AND_STAY));

        $this->symfonyRouter->expects($this->once())
            ->method('generate')
            ->will($this->returnValue($testUrl));

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $redirect = $this->router->redirectAfterSave(
            array(
                'route'      => 'test_route',
                'parameters' => array('id' => 1),
            ),
            array()
        );

        $this->assertEquals($testUrl, $redirect->getTargetUrl());
    }

    public function testSaveAndStayWithAccessGrantedRedirectAfterSave()
    {
        $testUrl = 'test\\url\\index.html';

        $this->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Router::ACTION_SAVE_AND_STAY));

        $this->symfonyRouter->expects($this->once())
            ->method('generate')
            ->will($this->returnValue($testUrl));

        $entity = new \stdClass();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($entity))
            ->will($this->returnValue(true));

        $redirect = $this->router->redirectAfterSave(
            array(
                'route'      => 'test_route',
                'parameters' => array('id' => 1),
            ),
            array(),
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
            ->will($this->returnValue(Router::ACTION_SAVE_AND_STAY));

        $this->symfonyRouter->expects($this->once())
            ->method('generate')
            ->will(
                $this->returnCallback(
                    function ($name, $parameters) use (&$testUrl1, &$testUrl2) {
                        if ($name === 'test_route1') {
                            return $testUrl1;
                        } elseif ($name === 'test_route2') {
                            return $testUrl2;
                        } else {
                            return '';
                        }
                    }
                )
            );

        $entity = new \stdClass();

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $this->identicalTo($entity))
            ->will($this->returnValue(false));

        $redirect = $this->router->redirectAfterSave(
            array(
                'route'      => 'test_route1',
                'parameters' => array('id' => 1),
            ),
            array(
                'route'      => 'test_route2',
                'parameters' => array('id' => 1),
            ),
            $entity
        );

        $this->assertEquals($testUrl2, $redirect->getTargetUrl());
    }

    public function testSaveAndCloseRedirectAfterSave()
    {
        $testUrl = 'save_and_close.html';

        $this->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Router::ACTION_SAVE_CLOSE));

        $this->symfonyRouter->expects($this->once())
            ->method('generate')
            ->will($this->returnValue($testUrl));

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $redirect = $this->router->redirectAfterSave(
            array(),
            array(
                'route'      => 'test_route',
                'parameters' => array('id' => 1),
            )
        );

        $this->assertEquals($testUrl, $redirect->getTargetUrl());
    }

    public function testWrongParametersRedirectAfterSave()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->router->redirectAfterSave(
            array(),
            array()
        );
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Request parameter "input_action" must be string, array is given.
     */
    public function testRedirectFailsWhenInputActionNotString()
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(['invalid_value']);

        $this->router->redirect([]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot parse route name from request parameter "input_action". Value of key "route" cannot be empty: {"route":""}
     */
    // @codingStandardsIgnoreEnd
    public function testRedirectFailsWhenRouteIsEmpty()
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => '']));

        $this->router->redirect([]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot parse route name from request parameter "input_action". Value of key "route" must be string: {"route":{"foo":"bar"}}
     */
    // @codingStandardsIgnoreEnd
    public function testRedirectFailsWhenRouteIsNotString()
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => ['foo' => 'bar']]));

        $this->router->redirect([]);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot parse route name from request parameter "input_action". Value of key "params" must be array: {"route":"foo","params":"bar"}
     */
    // @codingStandardsIgnoreEnd
    public function testRedirectFailsWhenRouteParamsIsNotArray()
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode(['route' => 'foo', 'params' => 'bar']));

        $this->router->redirect([]);
    }

    /**
     * @dataProvider redirectDataProvider
     */
    public function testRedirectWorks($expected, $data)
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(Router::ACTION_PARAMETER)
            ->willReturn(json_encode($data['actionParameters']));

        $expectedUrl = 'http://expected.com';
        $this->symfonyRouter->expects($this->once())
            ->method('generate')
            ->with($expected['route'], $expected['parameters'])
            ->willReturn($expectedUrl);

        $response = $this->router->redirect($data['context']);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * @return array
     */
    public function redirectDataProvider()
    {
        $expectedRoute = 'test_route';
        $expectedStaticParameterValue = 'OroCRM\Bundle\CallBundle\Entity\Call';
        $expectedStaticParameter = 'testStaticParameter';
        $expectedEntityIdParameter = 'id';
        $expectedId = 42;
        $entity = $this->getEntityStub($expectedId);
        $entityAsContextTestCase = [
            'expected' => [
                'route' => $expectedRoute,
                'parameters' => [
                    $expectedStaticParameter => $expectedStaticParameterValue,
                    $expectedEntityIdParameter => $expectedId
                ]
            ],
            'data' => [
                'actionParameters' => [
                    'route' => $expectedRoute,
                    'params' => [
                        $expectedStaticParameter => $expectedStaticParameterValue,
                        $expectedEntityIdParameter => '$id'
                    ]
                ],
                'context' => $entity
            ]
        ];

        $expectedSecondEntityIdParameter = 'secondId';
        $expectedSecondEntityId = 21;
        $firstEntityContextKey = 'firstEntity';
        $secondEntityContextKey = 'secondEntity';
        $arrayAsContextTestCase = [
            'expected' => [
                'route' => $expectedRoute,
                'parameters' => [
                    $expectedStaticParameter => $expectedStaticParameterValue,
                    $expectedEntityIdParameter => $expectedId,
                    $expectedSecondEntityIdParameter => $expectedSecondEntityId
                ]
            ],
            'data' => [
                'actionParameters' => [
                    'route' => $expectedRoute,
                    'params' => [
                        $expectedStaticParameter => $expectedStaticParameterValue,
                        $expectedEntityIdParameter => '$'.$firstEntityContextKey.'.id',
                        $expectedSecondEntityIdParameter => '$'.$secondEntityContextKey.'.id'
                    ]
                ],
                'context' => [
                    $firstEntityContextKey => $entity,
                    $secondEntityContextKey => $this->getEntityStub($expectedSecondEntityId)
                ]
            ]
        ];

        return [
            'with entity as context' => $entityAsContextTestCase,
            'with array as context' => $arrayAsContextTestCase,
        ];
    }

    /**
     * @param int $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityStub($id)
    {
        $entity = $this->getMock('StdClass', ['getId']);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $entity;
    }
}
