<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;

class TrackingVisitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingVisit
     */
    protected $trackingVisit;

    protected function setUp()
    {
        $this->trackingVisit = new TrackingVisit();
    }

    public function testId()
    {
        $this->assertNull($this->trackingVisit->getId());
    }

    /**
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     *
     * @dataProvider propertyProvider
     */
    public function testProperties($property, $value, $expected)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->assertNull(
            $propertyAccessor->getValue($this->trackingVisit, $property)
        );

        $propertyAccessor->setValue($this->trackingVisit, $property, $value);

        $this->assertEquals(
            $expected,
            $propertyAccessor->getValue($this->trackingVisit, $property)
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        $date = new \DateTime();

        return [
            ['visitorUid', 'a0cde23', 'a0cde23'],
            ['userIdentifier', '215', '215'],
            ['firstActionTime', $date, $date],
            ['lastActionTime', $date, $date],
            ['parsedUID', 458, 458],
            ['parsingCount', 1, 1],
            ['ip', '127.0.0.1', '127.0.0.1'],
        ];
    }
}