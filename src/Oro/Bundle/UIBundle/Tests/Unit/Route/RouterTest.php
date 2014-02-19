<?php
namespace Oro\Bundle\UIBundle\Tests\Route;

use Oro\Bundle\UIBundle\Route\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $route;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /**
     * @var Router
     */
    protected $router;

    public function setUp()
    {
        $this->request        = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->route          = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router         = new Router($this->request, $this->route, $this->securityFacade);
    }

    public function testSaveAndStayRedirectAfterSave()
    {
        $testUrl = 'test\\url\\index.html';

        $this->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Router::ACTION_SAVE_AND_STAY));

        $this->route->expects($this->once())
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

        $this->route->expects($this->once())
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

        $this->route->expects($this->once())
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
            ->will($this->returnValue('test'));

        $this->route->expects($this->once())
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
}
