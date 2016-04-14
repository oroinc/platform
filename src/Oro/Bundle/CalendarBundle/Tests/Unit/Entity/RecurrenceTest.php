<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new Recurrence();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new Recurrence();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['recurrence_type', 'daily'],
            ['interval', 99],
            ['instance', 3],
            ['day_of_week', ['monday', 'wednesday']],
            ['day_of_month', 28],
            ['month_of_year', 8],
            ['start_time', new \DateTime()],
            ['end_time', new \DateTime()],
        ];
    }
}
