<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use PHPUnit\Framework\TestCase;

class DateModifierProviderTest extends TestCase
{
    private DateModifierProvider $dateModifierProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->dateModifierProvider = new DateModifierProvider();
    }

    public function testDateParts(): void
    {
        $parts = $this->dateModifierProvider->getDateParts();
        $this->assertNotEmpty($parts);
        $this->assertCount(8, $parts);
    }

    public function testDateVariables(): void
    {
        $vars = $this->dateModifierProvider->getDateVariables();
        $this->assertNotEmpty($vars);
        $this->assertCount(8, $vars);
    }
}
