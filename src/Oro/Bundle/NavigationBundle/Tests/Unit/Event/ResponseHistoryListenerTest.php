<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\NavigationBundle\Event\ResponseHistoryListener;
use Oro\Bundle\NavigationBundle\Utils\NavigationHistoryLogger;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResponseHistoryListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var NavigationHistoryLogger|\PHPUnit\Framework\MockObject\MockObject */
    private $navigationHistoryLogger;

    /** @var ResponseHistoryListener */
    private $listener;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->navigationHistoryLogger = $this->createMock(NavigationHistoryLogger::class);

        $container = TestContainerBuilder::create()
            ->add('oro_navigation.navigation_history_logger', $this->navigationHistoryLogger)
            ->getContainer($this);

        $this->listener = new ResponseHistoryListener(
            $this->tokenAccessor,
            User::class,
            $container
        );
        $this->listener->addExcludedRoute('oro_default');
    }

    private function getRequest(string $route = 'test_route', string $format = 'html', string $method = 'GET'): Request
    {
        $request = new Request(['id' => 1], [], ['_route' => $route, '_route_params' => []]);
        $request->setRequestFormat($format);
        $request->setMethod($method);

        return $request;
    }

    private function getResponse(int $statusCode = 200): Response
    {
        return new Response('message', $statusCode);
    }

    private function getEvent(Request $request, Response $response, bool $isMasterRequest = true): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $isMasterRequest ? HttpKernelInterface::MASTER_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $response
        );
    }

    public function testForSubRequest(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('getUser');
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($this->getRequest(), $this->getResponse(), false)
        );
    }

    public function testForUnsupportedUser(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(AbstractUser::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($this->getRequest(), $this->getResponse())
        );
    }

    public function testForSupportedUser(): void
    {
        $request = $this->getRequest();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::once())
            ->method('logRequest')
            ->with(self::identicalTo($request));

        $this->listener->onResponse(
            $this->getEvent($request, $this->getResponse())
        );
    }

    public function testForUnsupportedStatusCode(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($this->getRequest(), $this->getResponse(201))
        );
    }

    public function testForExcludedRoute(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent(
                $this->getRequest('oro_default'),
                $this->getResponse()
            )
        );
    }

    public function testForUnsupportedRequestFormat(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent(
                $this->getRequest('test_route', 'json'),
                $this->getResponse()
            )
        );
    }

    public function testForUnsupportedHttpMethod(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent(
                $this->getRequest('test_route', 'html', 'POST'),
                $this->getResponse()
            )
        );
    }

    public function testForXmlHttpRequest(): void
    {
        $request = $this->getRequest();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($request, $this->getResponse())
        );
    }

    public function testForXmlHttpRequestAndHashNavigation(): void
    {
        $request = $this->getRequest();
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, '1');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::once())
            ->method('logRequest')
            ->with(self::identicalTo($request));

        $this->listener->onResponse(
            $this->getEvent($request, $this->getResponse())
        );
    }

    public function testForUnknownContentDisposition(): void
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $response->headers->set('Content-Disposition', 'another');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::once())
            ->method('logRequest')
            ->with(self::identicalTo($request));

        $this->listener->onResponse(
            $this->getEvent($request, $response)
        );
    }

    public function testForInlineContentDisposition(): void
    {
        $response = $this->getResponse();
        $response->headers->set('Content-Disposition', 'inline');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($this->getRequest(), $response)
        );
    }

    public function testForAttachmentContentDisposition(): void
    {
        $response = $this->getResponse();
        $response->headers->set('Content-Disposition', 'attachment; filename=file.html');

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->navigationHistoryLogger->expects(self::never())
            ->method('logRequest');

        $this->listener->onResponse(
            $this->getEvent($this->getRequest(), $response)
        );
    }
}
