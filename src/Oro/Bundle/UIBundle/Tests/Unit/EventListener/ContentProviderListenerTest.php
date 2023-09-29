<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager;
use Oro\Bundle\UIBundle\EventListener\ContentProviderListener;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentProviderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentProviderManager|\PHPUnit\Framework\MockObject\MockObject */
    private $contentProviderManager;

    /** @var ContentProviderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->contentProviderManager = $this->createMock(ContentProviderManager::class);

        $container = TestContainerBuilder::create()
            ->add(ContentProviderManager::class, $this->contentProviderManager)
            ->getContainer($this);

        $this->listener = new ContentProviderListener($container);
    }

    public function getEvent(Request $request, int $requestType): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        );
    }

    public function testSubRequest(): void
    {
        $request = Request::create('/test/url');
        $request->query->set('_enableContentProviders', 'test1,test2');
        $request->query->set('_displayContentProviders', 'test2');

        $this->contentProviderManager->expects(self::never())
            ->method(self::anything());

        $event = $this->getEvent($request, HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testNoData(): void
    {
        $request = Request::create('/test/url');

        $this->contentProviderManager->expects(self::never())
            ->method(self::anything());

        $event = $this->getEvent($request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testForEnable(): void
    {
        $request = Request::create('/test/url');
        $request->query->set('_enableContentProviders', 'test1,test2');

        $this->contentProviderManager->expects(self::exactly(2))
            ->method('enableContentProvider')
            ->withConsecutive(['test1'], ['test2']);

        $event = $this->getEvent($request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($event);
    }

    public function testForDisplay(): void
    {
        $request = Request::create('/test/url');
        $request->query->set('_displayContentProviders', 'test2');

        $this->contentProviderManager->expects(self::once())
            ->method('disableContentProvider')
            ->with('test1');
        $this->contentProviderManager->expects(self::once())
            ->method('getContentProviderNames')
            ->willReturn(['test1', 'test2']);

        $event = $this->getEvent($request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($event);
    }
}
