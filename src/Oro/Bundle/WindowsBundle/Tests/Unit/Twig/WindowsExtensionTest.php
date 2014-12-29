<?php

namespace Oro\Bundle\WindowsBundle\Tests\Twig;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension;

class WindowsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WindowsExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $environment
     */
    protected $environment;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->environment = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new WindowsExtension($this->securityContext, $this->entityManager);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('oro_windows_restore', $functions);
        $this->assertInstanceOf('Twig_Function_Method', $functions['oro_windows_restore']);
        $this->assertAttributeEquals('render', 'method', $functions['oro_windows_restore']);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_windows', $this->extension->getName());
    }

    public function testRenderNoUser()
    {
        $this->assertEmpty($this->extension->render($this->environment));
    }

    public function testRender()
    {
        $token = $this->getMockBuilder('stdClass')
            ->setMethods(array('getUser'))
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $user = $this->getMock('stdClass');

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $windowStateFoo = $this->createWindowState(array('cleanUrl' => 'foo'));
        $windowStateBar = $this->createWindowState(array('cleanUrl' => 'foo'));

        $userWindowStates = array($windowStateFoo, $windowStateBar);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with(array('user' => $user))
            ->will($this->returnValue($userWindowStates));

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroWindowsBundle:WindowsState')
            ->will($this->returnValue($repository));

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');

        $expectedOutput = 'RENDERED';
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroWindowsBundle::states.html.twig',
                array('windowStates' => array($windowStateFoo, $windowStateBar))
            )
            ->will($this->returnValue($expectedOutput));

        $this->assertEquals($expectedOutput, $this->extension->render($this->environment));
    }

    public function testRenderWithRemoveInvalidStates()
    {
        $token = $this->getMockBuilder('stdClass')
            ->setMethods(array('getUser'))
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $user = $this->getMock('stdClass');

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $normalWindowState = $this->createWindowState(array('cleanUrl' => 'foo'));
        $badWindowState = $this->createWindowState(array('url' => 'foo'));
        $emptyWindowState = $this->createWindowState();

        $userWindowStates = array($normalWindowState, $badWindowState, $emptyWindowState);

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findBy')
            ->with(array('user' => $user))
            ->will($this->returnValue($userWindowStates));

        $this->entityManager->expects($this->at(0))
            ->method('getRepository')
            ->with('OroWindowsBundle:WindowsState')
            ->will($this->returnValue($repository));

        $this->entityManager->expects($this->at(1))
            ->method('remove')
            ->with($badWindowState);

        $this->entityManager->expects($this->at(2))
            ->method('remove')
            ->with($emptyWindowState);

        $this->entityManager->expects($this->at(3))
            ->method('flush')
            ->with(array($badWindowState, $emptyWindowState));

        $expectedOutput = 'RENDERED';
        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroWindowsBundle::states.html.twig',
                array('windowStates' => array($normalWindowState))
            )
            ->will($this->returnValue($expectedOutput));

        $this->assertEquals($expectedOutput, $this->extension->render($this->environment));
    }

    public function testRenderWithoutUser()
    {
        $token = $this->getMockBuilder('stdClass')
            ->setMethods(array('getUser'))
            ->getMock();

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->entityManager->expects($this->never())->method($this->anything());

        $this->assertEquals('', $this->extension->render($this->environment));
    }

    public function testRenderWithoutToken()
    {
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null));

        $this->entityManager->expects($this->never())->method($this->anything());

        $this->assertEquals('', $this->extension->render($this->environment));
    }

    /**
     * @param string $cleanUrl
     * @param string $type
     * @param string $expectedUrl
     * @dataProvider renderFragmentDataProvider
     */
    public function testRenderFragment($cleanUrl, $type, $expectedUrl)
    {
        $windowState = $this->createWindowState(array('cleanUrl' => $cleanUrl, 'type' => $type));

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $expectedOutput = 'RENDERED';
        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with(
                $this->callback(
                    function ($url) use ($expectedUrl) {
                        $count = 0;
                        $cleanUrl = preg_replace('/&_wid=([a-z0-9]*)-([a-z0-9]*)/', '', $url, -1, $count);

                        return ($count === 1 && $cleanUrl == $expectedUrl);
                    }
                )
            )
            ->will($this->returnValue($expectedOutput));

        $this->entityManager->expects($this->never())->method($this->anything());

        $this->assertEquals($expectedOutput, $this->extension->renderFragment($this->environment, $windowState));
        $this->assertTrue($windowState->isRenderedSuccessfully());
    }

    /**
     * @return array
     */
    public function renderFragmentDataProvider()
    {
        return array(
            'url_without_parameters' => array(
                'widgetUrl'         => '/user/create',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?_widgetContainer=test'
            ),
            'url_with_parameters' => array(
                'widgetUrl'         => '/user/create?id=1',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test'
            ),
            'url_with_parameters_and_fragment' => array(
                'widgetUrl'         => '/user/create?id=1#group=date',
                'widgetType'        => 'test',
                'expectedWidgetUrl' => '/user/create?id=1&_widgetContainer=test#group=date'
            ),
        );
    }

    public function testRenderFragmentWithNotFoundHttpException()
    {
        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(array('cleanUrl' => $cleanUrl));

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with($cleanUrl)
            ->will($this->throwException(new NotFoundHttpException()));

        $this->entityManager->expects($this->at(0))
            ->method('remove')
            ->with($windowState);

        $this->entityManager->expects($this->at(1))
            ->method('flush')
            ->with($windowState);

        $this->assertEquals('', $this->extension->renderFragment($this->environment, $windowState));
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage This is exception was not caught.
     */
    public function testRenderFragmentWithGenericException()
    {
        $cleanUrl = '/foo/bar';
        $windowState = $this->createWindowState(array('cleanUrl' => $cleanUrl));

        $httpKernelExtension = $this->getHttpKernelExtensionMock();

        $this->environment->expects($this->once())
            ->method('getExtension')
            ->with('http_kernel')
            ->will($this->returnValue($httpKernelExtension));

        $httpKernelExtension->expects($this->once())
            ->method('renderFragment')
            ->with($cleanUrl)
            ->will($this->throwException(new \Exception('This is exception was not caught.')));

        $this->extension->renderFragment($this->environment, $windowState);
    }

    public function testRenderFragmentWithEmptyCleanUrl()
    {
        $windowState = $this->createWindowState();

        $this->environment->expects($this->never())->method($this->anything());
        $this->entityManager->expects($this->never())->method($this->anything());

        $this->assertEquals('', $this->extension->renderFragment($this->environment, $windowState));
        $this->assertFalse($windowState->isRenderedSuccessfully());
    }

    protected function createWindowState($data = null)
    {
        $state = new WindowsState();
        $state->setData($data);
        return $state;
    }

    protected function getHttpKernelExtensionMock()
    {
        return $this->getMockBuilder('Symfony\Bridge\Twig\Extension\HttpKernelExtension')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
