<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Model;

use Oro\Bundle\PlatformBundle\Model\UsageStat;
use PHPUnit\Framework\TestCase;

class UsageStatTest extends TestCase
{
    public function testCreateWithRequiredParameters(): void
    {
        $title = 'title';
        $usageStat = UsageStat::create($title);

        self::assertEquals($title, $usageStat->getTitle());
        self::assertNull($usageStat->getTooltip());
        self::assertNull($usageStat->getValue());
    }

    public function testCreate(): void
    {
        $title = 'title';
        $tooltip = 'tooltip';
        $value = 'value';
        $usageStat = UsageStat::create($title, $tooltip, $value);

        self::assertEquals($title, $usageStat->getTitle());
        self::assertEquals($tooltip, $usageStat->getTooltip());
        self::assertEquals($value, $usageStat->getValue());
    }
}
