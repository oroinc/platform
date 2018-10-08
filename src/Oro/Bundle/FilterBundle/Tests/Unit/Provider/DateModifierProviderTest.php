<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;

class DateModifierProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateModifierProvider */
    protected $dateModifierProvider;

    protected function setUp()
    {
        $this->dateModifierProvider = new DateModifierProvider();
    }

    public function testDateParts()
    {
        $parts = $this->dateModifierProvider->getDateParts();
        $this->assertNotEmpty($parts);
        $this->assertCount(8, $parts);
    }

    public function testDateVariables()
    {
        $vars = $this->dateModifierProvider->getDateVariables();
        $this->assertNotEmpty($vars);
        $this->assertCount(8, $vars);
    }
}
