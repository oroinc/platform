<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\EventListener\ContentProviderListener;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ContentProviderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var ContentProviderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $contentProviderManager;

    /** @var ContentProviderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(GetResponseEvent::class);
        $this->event->expects($this->any())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->contentProviderManager = $this->createMock(ContentProviderManager::class);

        $container = TestContainerBuilder::create()
            ->add('oro_ui.content_provider.manager', $this->contentProviderManager)
            ->getContainer($this);

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
            ->willReturnMap([
                ['_enableContentProviders', null, 'test1,test2']
            ]);
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToDisplay()
    {
        $this->contentProviderManager->expects($this->once())
            ->method('disableContentProvider')
            ->with('test1');

        $this->contentProviderManager->expects($this->once())
            ->method('getContentProviderNames')
            ->willReturn(['test1', 'test2']);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['_displayContentProviders', null, 'test2']
            ]);
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }
}
