<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\BodyListenerInterface;
use Oro\Bundle\ApiBundle\EventListener\UpdateListBodyListenerDecorator;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UpdateListBodyListenerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private const LIST_ROUTE_NAME = 'list_route';

    /** @var Request */
    private $request;

    /** @var RequestEvent */
    private $event;

    /** @var BodyListenerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $bodyListener;

    /** @var UpdateListBodyListenerDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->request = new Request();
        $this->request->headers->set('Content-Type', 'text/html');

        $this->event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->bodyListener = $this->createMock(BodyListenerInterface::class);
        $routes = $this->createMock(RestRoutes::class);
        $routes->expects(self::any())
            ->method('getListRouteName')
            ->willReturn(self::LIST_ROUTE_NAME);

        $this->decorator = new UpdateListBodyListenerDecorator($this->bodyListener, $routes);
    }

    public function testOnKernelRequestWithNonApiRequest(): void
    {
        $this->bodyListener->expects(self::once())
            ->method('onKernelRequest')
            ->willReturnCallback(function (RequestEvent $event) {
                self::assertEquals(
                    'text/html',
                    $event->getRequest()->headers->get('Content-Type')
                );
            });

        $this->decorator->onKernelRequest($this->event);

        self::assertEquals('text/html', $this->request->headers->get('Content-Type'));
    }

    public function testOnKernelRequestWithItemAction(): void
    {
        $this->request->setMethod(Request::METHOD_PATCH);
        $this->request->attributes->set('_route', 'item_route');

        $this->bodyListener->expects(self::once())
            ->method('onKernelRequest')
            ->willReturnCallback(function (RequestEvent $event) {
                self::assertEquals(
                    'text/html',
                    $event->getRequest()->headers->get('Content-Type')
                );
            });

        $this->decorator->onKernelRequest($this->event);

        self::assertEquals('text/html', $this->request->headers->get('Content-Type'));
    }

    public function testOnKernelRequestWithNonUpdateListAction(): void
    {
        $this->request->setMethod(Request::METHOD_GET);
        $this->request->attributes->set('_route', self::LIST_ROUTE_NAME);

        $this->bodyListener->expects(self::once())
            ->method('onKernelRequest')
            ->willReturnCallback(function (RequestEvent $event) {
                self::assertEquals(
                    'text/html',
                    $event->getRequest()->headers->get('Content-Type')
                );
            });

        $this->decorator->onKernelRequest($this->event);

        self::assertEquals('text/html', $this->request->headers->get('Content-Type'));
    }

    public function testOnKernelRequestWithUpdateListActionAndFormUrlencodedContentType(): void
    {
        $this->request->setMethod(Request::METHOD_PATCH);
        $this->request->attributes->set('_route', self::LIST_ROUTE_NAME);
        $this->request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $this->bodyListener->expects(self::once())
            ->method('onKernelRequest')
            ->willReturnCallback(function (RequestEvent $event) {
                self::assertEquals(
                    'application/x-www-form-urlencoded',
                    $event->getRequest()->headers->get('Content-Type')
                );
            });

        $this->decorator->onKernelRequest($this->event);

        self::assertEquals('application/x-www-form-urlencoded', $this->request->headers->get('Content-Type'));
    }

    public function testOnKernelRequestWithUpdateListAction(): void
    {
        $this->request->setMethod(Request::METHOD_PATCH);
        $this->request->attributes->set('_route', self::LIST_ROUTE_NAME);

        $this->bodyListener->expects(self::once())
            ->method('onKernelRequest')
            ->willReturnCallback(function (RequestEvent $event) {
                self::assertEquals(
                    'application/x-www-form-urlencoded',
                    $event->getRequest()->headers->get('Content-Type')
                );
            });

        $this->decorator->onKernelRequest($this->event);

        self::assertEquals('text/html', $this->request->headers->get('Content-Type'));
    }
}
