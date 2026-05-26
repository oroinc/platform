<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Provider;

use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

final class DraftSessionUuidProviderTest extends TestCase
{
    private RequestContextAwareInterface&MockObject $router;

    private RequestContext&MockObject $requestContext;

    private DraftSessionUuidProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->requestContext = $this->createMock(RequestContext::class);

        $this->provider = new DraftSessionUuidProvider($this->router, 'draftSessionUuid');
    }

    public function testGetDraftSessionUuidReturnsValueFromRequestContext(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('draftSessionUuid')
            ->willReturn('session-uuid-1');

        self::assertSame('session-uuid-1', $this->provider->getDraftSessionUuid());
    }

    public function testGetDraftSessionUuidReturnsNullWhenParameterIsMissing(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('draftSessionUuid')
            ->willReturn(null);

        self::assertNull($this->provider->getDraftSessionUuid());
    }

    public function testGetDraftSessionUuidReturnsNullWhenParameterIsEmptyString(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('draftSessionUuid')
            ->willReturn('');

        self::assertNull($this->provider->getDraftSessionUuid());
    }

    public function testGetDraftSessionUuidReturnsNullWhenParameterIsNotAString(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('draftSessionUuid')
            ->willReturn(42);

        self::assertNull($this->provider->getDraftSessionUuid());
    }
}
