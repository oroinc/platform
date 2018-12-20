<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserAgentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UserAgentProvider
     */
    protected $userAgentProvider;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->userAgentProvider = new UserAgentProvider($this->requestStack);
    }

    /**
     * @dataProvider getUserAgentDataProvider
     * @param bool $isRequest
     * @param string $userAgentName
     */
    public function testGetUserAgent($isRequest, $userAgentName)
    {
        $request = null;
        if ($isRequest) {
            /** @var HeaderBag|\PHPUnit\Framework\MockObject\MockObject $request */
            $headers = $this->createMock('\Symfony\Component\HttpFoundation\HeaderBag');
            /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
            $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
            $request->headers = $headers;
            $headers->expects($this->once())
                ->method('get')
                ->with('User-Agent')
                ->willReturn($userAgentName);
        }

        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $userAgent = $this->userAgentProvider->getUserAgent();
        if ($userAgentName === null) {
            $userAgentName = UserAgentProvider::UNKNOWN_USER_AGENT;
        }

        $this->assertEquals($userAgentName, $userAgent->getUserAgent());
    }

    /**
     * @return array
     */
    public function getUserAgentDataProvider()
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

    public function testGetUserAgentCache()
    {
        $this->requestStack->expects($this->exactly(2))
            ->method('getMasterRequest')
            ->willReturn(null);

        $userAgent = $this->userAgentProvider->getUserAgent();

        $userAgentName = UserAgentProvider::UNKNOWN_USER_AGENT;

        $this->assertEquals($userAgentName, $userAgent->getUserAgent());
        $this->assertSame($userAgent, $this->userAgentProvider->getUserAgent());
    }
}
