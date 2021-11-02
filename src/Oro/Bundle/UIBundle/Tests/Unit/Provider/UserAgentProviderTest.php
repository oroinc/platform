<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserAgentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var UserAgentProvider */
    private $userAgentProvider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->userAgentProvider = new UserAgentProvider($this->requestStack);
    }

    /**
     * @dataProvider getUserAgentDataProvider
     */
    public function testGetUserAgent(bool $isRequest, ?string $userAgentName): void
    {
        $request = null;
        if ($isRequest) {
            $headers = $this->createMock(HeaderBag::class);
            $request = $this->createMock(Request::class);
            $request->headers = $headers;
            $headers->expects(self::once())
                ->method('get')
                ->with('User-Agent')
                ->willReturn($userAgentName);
        }

        $this->requestStack->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $userAgent = $this->userAgentProvider->getUserAgent();
        if ($userAgentName === null) {
            $userAgentName = UserAgentProvider::UNKNOWN_USER_AGENT;
        }

        self::assertEquals($userAgentName, $userAgent->getUserAgent());
    }

    public function getUserAgentDataProvider(): array
    {
        return [
            [
                'isRequest' => true,
                'userAgent' => 'safari',
            ],
            [
                'isRequest' => false,
                'userAgent' => UserAgentProvider::UNKNOWN_USER_AGENT,
            ],
            [
                'isRequest' => true,
                'userAgent' => null,
            ],
        ];
    }

    public function testGetUserAgentCache(): void
    {
        $this->requestStack->expects(self::exactly(2))
            ->method('getMainRequest')
            ->willReturn(null);

        $userAgent = $this->userAgentProvider->getUserAgent();

        $userAgentName = UserAgentProvider::UNKNOWN_USER_AGENT;

        self::assertEquals($userAgentName, $userAgent->getUserAgent());
        self::assertSame($userAgent, $this->userAgentProvider->getUserAgent());
    }
}
