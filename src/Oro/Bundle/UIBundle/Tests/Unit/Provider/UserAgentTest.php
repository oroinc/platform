<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgent;
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    public function testWithoutUserAgentString(): void
    {
        $agent = new UserAgent(null);

        $this->assertSame('', $agent->getUserAgent());
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testWithEmptyUserAgentString(): void
    {
        $agent = new UserAgent('');

        $this->assertSame('', $agent->getUserAgent());
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testMobileAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9';
        $agent = new UserAgent($userAgent);

        $this->assertTrue(isset($agent[UserAgent::USER_AGENT]));
        $this->assertTrue(isset($agent[UserAgent::MOBILE]));
        $this->assertTrue(isset($agent[UserAgent::DESKTOP]));

        $this->assertEquals($userAgent, $agent->getUserAgent());
        $this->assertEquals($userAgent, $agent[UserAgent::USER_AGENT]);
        $this->assertTrue($agent->isMobile());
        $this->assertTrue($agent[UserAgent::MOBILE]);
        $this->assertFalse($agent->isDesktop());
        $this->assertFalse($agent[UserAgent::DESKTOP]);
    }

    public function testDesktopAgent(): void
    {
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)';
        $agent = new UserAgent($userAgent);

        $this->assertTrue(isset($agent[UserAgent::USER_AGENT]));
        $this->assertTrue(isset($agent[UserAgent::MOBILE]));
        $this->assertTrue(isset($agent[UserAgent::DESKTOP]));

        $this->assertEquals($userAgent, $agent->getUserAgent());
        $this->assertEquals($userAgent, $agent[UserAgent::USER_AGENT]);
        $this->assertFalse($agent->isMobile());
        $this->assertFalse($agent[UserAgent::MOBILE]);
        $this->assertTrue($agent->isDesktop());
        $this->assertTrue($agent[UserAgent::DESKTOP]);
    }

    public function testToString(): void
    {
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)';
        $agent = new UserAgent($userAgent);

        $this->assertEquals($userAgent, $agent->toString());
    }

    public function testArrayAccessSetThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $agent = new UserAgent('');
        $agent[UserAgent::USER_AGENT] = 'val';
    }

    public function testArrayAccessUnsetThrowsException(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $agent = new UserAgent('');
        unset($agent[UserAgent::USER_AGENT]);
    }
}
