<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;

class DateModifierProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateModifierProvider */
    protected $dateModifierProvider;

    public function setUp()
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
