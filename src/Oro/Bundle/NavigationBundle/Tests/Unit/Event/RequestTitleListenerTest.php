<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\NavigationBundle\Event\RequestTitleListener;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class RequestTitleListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $titleService;

    /** @var RequestTitleListener */
    protected $listener;

    protected function setUp()
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $container = TestContainerBuilder::create()
            ->add('oro_navigation.title_service', $this->titleService)
            ->getContainer($this);

        $this->listener = new RequestTitleListener($container);
    }

    public function testRequestForMasterRequest()
    {
        /** @var $request \PHPUnit_Framework_MockObject_MockObject */
        $request = $this->getRequest(1);

        $this->titleService->expects($this->once())
            ->method('loadByRoute')
            ->with('test_route');

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(true));

        $this->listener->onKernelRequest($event);
    }

    public function testRequestForSubRequest()
    {
        /** @var $request \PHPUnit_Framework_MockObject_MockObject */
        $request = $this->getRequest(0);

        $this->titleService->expects($this->never())
            ->method('loadByRoute');

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->will($this->returnValue(false));

        $this->listener->onKernelRequest($event);
    }

    /**
     * Creates request mock object
     *
     * @param  int     $invokeTimes
     * @return Request
     */
    private function getRequest($invokeTimes)
    {
        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');

        $request->expects($this->exactly($invokeTimes))
            ->method('getRequestFormat')
            ->will($this->returnValue('html'));

        $request->expects($this->exactly($invokeTimes))
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $request->expects($this->exactly($invokeTimes))
            ->method('isXmlHttpRequest')
            ->will($this->returnValue(false));

        $invokationMoker = $request->expects($this->exactly($invokeTimes))
            ->method('get')
            ->will($this->returnValue('test_route'));

        // used this trick due to bug in phpUnit
        // https://github.com/sebastianbergmann/phpunit/issues/270
        if ($invokeTimes) {
            $invokationMoker->with('_route');
        }

        return $request;
    }
}
