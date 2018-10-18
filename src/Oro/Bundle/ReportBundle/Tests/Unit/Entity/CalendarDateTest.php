<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Entity;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CalendarDateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new CalendarDate(), [
            ['id', 42],
            ['date', new \DateTime()]
        ]);
    }
}
