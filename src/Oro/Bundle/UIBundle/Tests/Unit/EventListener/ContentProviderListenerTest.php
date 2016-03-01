<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\EventListener\ContentProviderListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentProviderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentProviderManager;

    /**
     * @var ContentProviderListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->any())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::MASTER_REQUEST));
        $this->contentProviderManager = $this
            ->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())->method('get')
            ->with('oro_ui.content_provider.manager')
            ->willReturn($this->contentProviderManager);

        $this->listener = new ContentProviderListener($container);
    }

    public function testOnKernelViewNoData()
    {
        $request = Request::create('/test/url');
        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $this->contentProviderManager->expects($this->never())
            ->method($this->anything());
        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToEnable()
    {
        $this->contentProviderManager->expects($this->exactly(2))
            ->method('enableContentProvider');
        $this->contentProviderManager->expects($this->at(0))
            ->method('enableContentProvider')
            ->with('test1');
        $this->contentProviderManager->expects($this->at(1))
            ->method('enableContentProvider')
            ->with('test2');

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('_enableContentProviders', null, false, 'test1,test2')
                    )
                )
            );
        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToDisplay()
    {
        $testContentProviderOne = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->once())
            ->method('setEnabled')
            ->with(false);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test1'));

        $testContentProviderTwo = $this->getMockBuilder('Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface')
            ->setMethods(array('setEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderTwo->expects($this->never())
            ->method('setEnabled');
        $testContentProviderTwo->expects($this->atLeastOnce())
            ->method('getName')
            ->will($this->returnValue('test2'));

        $providers = array(
            $testContentProviderOne,
            $testContentProviderTwo
        );

        $this->contentProviderManager->expects($this->once())
            ->method('getContentProviders')
            ->will($this->returnValue($providers));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('_displayContentProviders', null, false, 'test2')
                    )
                )
            );
        $this->event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->listener->onKernelRequest($this->event);
    }
}
