<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\EventListener\ContentProviderListener;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ContentProviderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var ContentProviderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $contentProviderManager;

    /** @var ContentProviderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->event = $this->createMock(RequestEvent::class);
        $this->event->expects(self::any())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->contentProviderManager = $this->createMock(ContentProviderManager::class);

        $container = TestContainerBuilder::create()
            ->add('oro_ui.content_provider.manager', $this->contentProviderManager)
            ->getContainer($this);

        $this->listener = new ContentProviderListener($container);
    }

    public function testOnKernelViewNoData(): void
    {
        $request = Request::create('/test/url');
        $this->event->expects(self::any())
            ->method('getRequest')
            ->willReturn($request);
        $this->contentProviderManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToEnable(): void
    {
        $this->contentProviderManager->expects(self::exactly(2))
            ->method('enableContentProvider');
        $this->contentProviderManager->expects(self::exactly(2))
            ->method('enableContentProvider')
            ->withConsecutive(['test1'], ['test2']);

        $request = $this->createMock(Request::class);

        $request->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['_enableContentProviders', null, 'test1,test2']
            ]);
        $this->event->expects(self::any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }

    public function testOnKernelViewToDisplay(): void
    {
        $this->contentProviderManager->expects(self::once())
            ->method('disableContentProvider')
            ->with('test1');

        $this->contentProviderManager->expects(self::once())
            ->method('getContentProviderNames')
            ->willReturn(['test1', 'test2']);

        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['_displayContentProviders', null, 'test2']
            ]);
        $this->event->expects(self::any())
            ->method('getRequest')
            ->willReturn($request);

        $this->listener->onKernelRequest($this->event);
    }
}
