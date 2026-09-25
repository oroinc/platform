<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Oro\Bundle\LayoutBundle\Cache\PageCacheWarmer;
use Oro\Bundle\PlatformBundle\Provider\AbstractPageRequestProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Service\ResetInterface;

class PageCacheWarmerTest extends TestCase
{
    private HttpKernelInterface&MockObject $httpKernel;
    private LoggerInterface&MockObject $logger;
    private ResetInterface&MockObject $servicesResetter;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpKernel = $this->createMock(HttpKernelInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->servicesResetter = $this->createMock(ResetInterface::class);
    }

    private function getWarmer(iterable $providers, bool $withResetter = true): PageCacheWarmer
    {
        return new PageCacheWarmer(
            $providers,
            $this->httpKernel,
            $this->logger,
            $withResetter ? $this->servicesResetter : null
        );
    }

    private function getProvider(array $requests): AbstractPageRequestProvider&MockObject
    {
        $provider = $this->createMock(AbstractPageRequestProvider::class);
        $provider->expects(self::once())
            ->method('getRequests')
            ->willReturn($requests);

        return $provider;
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->getWarmer([])->isOptional());
    }

    public function testWarmUpHandlesEachRequestAndResetsServicesAfterEachOne(): void
    {
        $request1 = Request::create('http://example.com/');
        $request2 = Request::create('http://example.com/product/1');
        $request3 = Request::create('http://example.com/customer/user/login');

        $this->httpKernel->expects(self::exactly(3))
            ->method('handle')
            ->willReturnCallback(function (Request $request) use ($request1, $request2, $request3) {
                self::assertContains($request, [$request1, $request2, $request3]);

                return new Response();
            });
        $this->servicesResetter->expects(self::exactly(3))
            ->method('reset');
        $this->logger->expects(self::never())
            ->method('warning');

        $warmer = $this->getWarmer([
            $this->getProvider([$request1, $request2]),
            $this->getProvider([$request3]),
        ]);

        self::assertSame([], $warmer->warmUp('/cache'));
    }

    public function testWarmUpSkipsUnsupportedProvidersAndNonRequestItems(): void
    {
        $request = Request::create('http://example.com/');

        $this->httpKernel->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($request))
            ->willReturn(new Response());
        $this->servicesResetter->expects(self::once())
            ->method('reset');

        $warmer = $this->getWarmer([
            new \stdClass(),
            $this->getProvider([null, 'not a request', $request]),
        ]);

        self::assertSame([], $warmer->warmUp('/cache'));
    }

    public function testWarmUpLogsWarningAndContinuesWhenPageHandlingFails(): void
    {
        $failingRequest = Request::create('http://example.com/failing');
        $request = Request::create('http://example.com/');
        $exception = new \RuntimeException('Something went wrong');

        $this->httpKernel->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(function (Request $handled) use ($failingRequest, $exception) {
                if ($handled === $failingRequest) {
                    throw $exception;
                }

                return new Response();
            });
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to warmup page cache: {message}',
                ['message' => 'Something went wrong', 'exception' => $exception]
            );
        // the services are reset after the failed page as well
        $this->servicesResetter->expects(self::exactly(2))
            ->method('reset');

        $warmer = $this->getWarmer([$this->getProvider([$failingRequest, $request])]);

        self::assertSame([], $warmer->warmUp('/cache'));
    }

    public function testWarmUpWorksWithoutServicesResetter(): void
    {
        $request = Request::create('http://example.com/');

        $this->httpKernel->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($request))
            ->willReturn(new Response());
        $this->servicesResetter->expects(self::never())
            ->method('reset');

        $warmer = $this->getWarmer([$this->getProvider([$request])], false);

        self::assertSame([], $warmer->warmUp('/cache'));
    }
}
