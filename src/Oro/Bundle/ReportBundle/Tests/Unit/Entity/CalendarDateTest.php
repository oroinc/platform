<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class CalendarDateTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new CalendarDate(), [
            ['id', 42],
            ['date', new \DateTime()]
        ]);
    }
}
