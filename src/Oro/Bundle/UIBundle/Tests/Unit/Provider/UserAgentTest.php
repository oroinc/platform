<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\UserAgent;

class UserAgentTest extends \PHPUnit_Framework_TestCase
{
    public function testWithoutUserAgentString()
    {
        $agent = new UserAgent();

        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testWithEmptyUserAgentString()
    {
        $agent = new UserAgent('');

        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testDependedPropertiesUnset()
    {
        $agent = new UserAgent(
            'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9'
        );

        $this->assertFalse($agent->isDesktop());
        $this->assertTrue($agent->isMobile());

        unset($agent[UserAgent::USER_AGENT]);
        $this->assertFalse(isset($agent[UserAgent::USER_AGENT]));
        $this->assertFalse(isset($agent[UserAgent::MOBILE]));
        $this->assertFalse(isset($agent[UserAgent::DESKTOP]));
        $this->assertNull($agent[UserAgent::USER_AGENT]);
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }

    public function testChangeUserAgentString()
    {
        $agent = new UserAgent(
            'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9'
        );

        $this->assertFalse($agent->isDesktop());
        $this->assertTrue($agent->isMobile());

        $agent[UserAgent::USER_AGENT] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)';
        $this->assertFalse(isset($agent[UserAgent::MOBILE]));
        $this->assertFalse(isset($agent[UserAgent::DESKTOP]));
        $this->assertEquals(
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; DigExt)',
            $agent[UserAgent::USER_AGENT]
        );
        $this->assertTrue($agent->isDesktop());
        $this->assertFalse($agent->isMobile());
    }
}
