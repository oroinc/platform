<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Passport\Badge;

use Oro\Bundle\SecurityBundle\Authentication\Passport\Badge\CaptchaBadge;
use PHPUnit\Framework\TestCase;

class CaptchaBadgeTest extends TestCase
{
    public function testGetToken(): void
    {
        $token = 'test-token';
        $badge = new CaptchaBadge($token);

        $this->assertSame($token, $badge->getToken());
    }

    public function testGetTokenWithNull(): void
    {
        $badge = new CaptchaBadge(null);

        $this->assertNull($badge->getToken());
    }

    public function testIsResolvedInitiallyFalse(): void
    {
        $badge = new CaptchaBadge('test-token');

        $this->assertFalse($badge->isResolved());
    }

    public function testMarkResolved(): void
    {
        $badge = new CaptchaBadge('test-token');
        $badge->markResolved();

        $this->assertTrue($badge->isResolved());
    }
}
