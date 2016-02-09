<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Provider\UserAgentProvider;

class UserAgentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserAgentProvider
     */
    protected $userAgentProvider;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->userAgentProvider = new UserAgentProvider($this->requestStack);
    }

    /**
     * @dataProvider getUserAgentDataProvider
     * @param bool $isRequest
     * @param string $userAgentName
     */
    public function testGetUserAgent($isRequest, $userAgentName)
    {
        $count = 2;
        $request = null;
        if ($isRequest) {
            /** @var HeaderBag|\PHPUnit_Framework_MockObject_MockObject $request */
            $headers = $this->getMock('\Symfony\Component\HttpFoundation\HeaderBag');
            /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
            $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
            $request->headers = $headers;
            $headers->expects($this->exactly($count))
                ->method('get')
                ->with('User-Agent')
                ->willReturn($userAgentName);
        }

        $this->requestStack->expects($this->exactly($count))
            ->method('getMasterRequest')
            ->willReturn($request);

        $userAgent = $this->userAgentProvider->getUserAgent();
        if ($userAgentName === null) {
            $userAgentName = UserAgentProvider::UNKNOWN_USER_AGENT;
        }

        $this->assertEquals($userAgentName, $userAgent->getUserAgent());
        $this->assertSame($userAgent, $this->userAgentProvider->getUserAgent());
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
}
