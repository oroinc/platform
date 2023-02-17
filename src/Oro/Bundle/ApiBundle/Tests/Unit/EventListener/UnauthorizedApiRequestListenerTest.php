<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\EventListener;

use Oro\Bundle\ApiBundle\EventListener\UnauthorizedApiRequestListener;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ApiBundle\Request\Rest\RequestActionHandler;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class UnauthorizedApiRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestActionHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var ApiRequestHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $apiRequestHelper;

    /** @var UnauthorizedApiRequestListener */
    private $listener;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestActionHandler::class);
        $this->apiRequestHelper = $this->createMock(ApiRequestHelper::class);

        $container = TestContainerBuilder::create()
            ->add(RequestActionHandler::class, $this->handler)
            ->getContainer($this);

        $this->listener = new UnauthorizedApiRequestListener(
            $container,
            $this->apiRequestHelper
        );
    }

    private function getEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );
    }

    public function testGetSubscribedServices(): void
    {
        self::assertEquals(
            [
                RequestActionHandler::class
            ],
            UnauthorizedApiRequestListener::getSubscribedServices()
        );
    }

    public function testForAuthorizedRequest(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $response = new Response('', Response::HTTP_OK);

        $this->apiRequestHelper->expects(self::never())
            ->method('isApiRequest');
        $this->handler->expects(self::never())
            ->method('handleUnhandledError');

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($response, $event->getResponse());
    }

    public function testForUnauthorizedNotApiRequest(): void
    {
        $request = Request::create('http://test.com/product/view/1');
        $response = new Response('', Response::HTTP_UNAUTHORIZED);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(false);
        $this->handler->expects(self::never())
            ->method('handleUnhandledError');

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($response, $event->getResponse());
    }

    public function testForUnauthorizedApiRequestWithoutWwwAuthenticateHeader(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $response = new Response('', Response::HTTP_UNAUTHORIZED);

        $expectedUnauthorizedHttpException = new HttpException(
            Response::HTTP_UNAUTHORIZED,
            '',
            null,
            [],
            0
        );
        $newResponse = new Response('', Response::HTTP_UNAUTHORIZED);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->handler->expects(self::once())
            ->method('handleUnhandledError')
            ->with(self::identicalTo($request), $expectedUnauthorizedHttpException)
            ->willReturn($newResponse);

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($newResponse, $event->getResponse());
    }

    public function testForUnauthorizedApiRequestWithWwwAuthenticateHeader(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $response = new Response('', Response::HTTP_UNAUTHORIZED);
        $response->headers->set('other', 'other header value');
        $response->headers->set('www-authenticate', 'www authenticate header value');

        $expectedUnauthorizedHttpException = new HttpException(
            Response::HTTP_UNAUTHORIZED,
            '',
            null,
            ['WWW-Authenticate' => 'www authenticate header value'],
            0
        );
        $newResponse = new Response('', Response::HTTP_UNAUTHORIZED);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->handler->expects(self::once())
            ->method('handleUnhandledError')
            ->with(self::identicalTo($request), $expectedUnauthorizedHttpException)
            ->willReturn($newResponse);

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($newResponse, $event->getResponse());
    }

    public function testForUnauthorizedApiRequestWithNotEmptyResponseContent(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $response = new Response('test content', Response::HTTP_UNAUTHORIZED);

        $expectedUnauthorizedHttpException = new HttpException(
            Response::HTTP_UNAUTHORIZED,
            'test content',
            null,
            [],
            0
        );
        $newResponse = new Response('', Response::HTTP_UNAUTHORIZED);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->handler->expects(self::once())
            ->method('handleUnhandledError')
            ->with(self::identicalTo($request), $expectedUnauthorizedHttpException)
            ->willReturn($newResponse);

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($newResponse, $event->getResponse());
    }

    public function testForUnauthorizedApiRequestWhenResponseContentIsFalse(): void
    {
        $request = Request::create('http://test.com/api/products/1');
        $response = new StreamedResponse(null, Response::HTTP_UNAUTHORIZED);

        $expectedUnauthorizedHttpException = new HttpException(
            Response::HTTP_UNAUTHORIZED,
            '',
            null,
            [],
            0
        );
        $newResponse = new Response('', Response::HTTP_UNAUTHORIZED);

        $this->apiRequestHelper->expects(self::once())
            ->method('isApiRequest')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->handler->expects(self::once())
            ->method('handleUnhandledError')
            ->with(self::identicalTo($request), $expectedUnauthorizedHttpException)
            ->willReturnCallback(function (Request $request, HttpException $error) use ($newResponse) {
                self::assertSame('', $error->getMessage());

                return $newResponse;
            });

        $event = $this->getEvent($request, $response);
        $this->listener->onKernelResponse($event);
        self::assertSame($newResponse, $event->getResponse());
    }
}
