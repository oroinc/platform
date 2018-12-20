<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgent;

class UserAgentTest extends \PHPUnit\Framework\TestCase
{
    public function testWithoutUserAgentString()
    {
        $agent = new UserAgent(null);

        $this->assertSame('', $agent->getUserAgent());
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testWithEmptyUserAgentString()
    {
        $agent = new UserAgent('');

        $this->assertSame('', $agent->getUserAgent());
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testMobileAgent()
    {
        $userAgent = 'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9';
        $agent     = new UserAgent($userAgent);

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

    public function testDesktopAgent()
    {
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)';
        $agent     = new UserAgent($userAgent);

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

    public function testToString()
    {
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)';
        $agent     = new UserAgent($userAgent);

        $this->assertEquals($userAgent, $agent->toString());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessSetThrowsException()
    {
        $agent                        = new UserAgent('');
        $agent[UserAgent::USER_AGENT] = 'val';
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testArrayAccessUnsetThrowsException()
    {
        $agent = new UserAgent('');
        unset($agent[UserAgent::USER_AGENT]);
    }
}
