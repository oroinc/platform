<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Oro\Bundle\UserBundle\Security\SkippedLogAttemptsFirewallsProvider;

class SkippedLogAttemptsFirewallsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testProvider(): void
    {
        $provider = new SkippedLogAttemptsFirewallsProvider();
        self::assertEmpty($provider->getSkippedFirewalls());
        $provider->addSkippedFirewall('first');
        $provider->addSkippedFirewall('second');
        self::assertEquals(['first', 'second'], $provider->getSkippedFirewalls());
    }
}
