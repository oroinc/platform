<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\EventListener\ContentProviderListener;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentProviderListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var ContentProviderManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentProviderManager;

    /**
     * @var ContentProviderListener
     */
    private $listener;

    protected function setUp()
    {
        $this->event = $this->createMock(GetResponseEvent::class);
        $this->event->expects($this->any())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->contentProviderManager = $this->createMock(ContentProviderManager::class);

        /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->any())
            ->method('get')
            ->with(ContentProviderManager::class)
            ->willReturn($this->contentProviderManager);

        $this->listener = new ContentProviderListener($container);
    }

    public function testOnKernelViewNoData()
    {
        $request = Request::create('/test/url');
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
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

        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['_enableContentProviders', null, 'test1,test2']
                ]
            );
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToDisplay()
    {
        $testContentProviderOne = $this
            ->getMockBuilder(ContentProviderInterface::class)
            ->setMethods(array('setEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderOne->expects($this->once())
            ->method('setEnabled')
            ->with(false);
        $testContentProviderOne->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('test1');

        $testContentProviderTwo = $this
            ->getMockBuilder(ContentProviderInterface::class)
            ->setMethods(array('setEnabled', 'getName'))
            ->getMockForAbstractClass();
        $testContentProviderTwo->expects($this->never())
            ->method('setEnabled');
        $testContentProviderTwo->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('test2');

        $providers = array(
            $testContentProviderOne,
            $testContentProviderTwo
        );

        $this->contentProviderManager->expects($this->once())
            ->method('getContentProviders')
            ->willReturn($providers);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['_displayContentProviders', null, 'test2']
                ]
            );
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }
}
