<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Dateparts;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class DatePartsTest extends OrmTestCase
{
    const FAKE_ENTITY = 'Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\Entity\Fake';

    /**
     * @var EntityManagerMock
     */
    protected $em;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();

        $config = $this->em->getConfiguration();
        $config->setProxyNamespace('Luxifer\Tests\Proxies');

        $config->addCustomDatetimeFunction('date', 'Luxifer\DQL\Datetime\Date');
        $config->addCustomDatetimeFunction('datediff', 'Luxifer\DQL\Datetime\DateDiff');
        $config->addCustomDatetimeFunction('dayofmonth', 'Luxifer\DQL\Datetime\DayOfMonth');
        $config->addCustomDatetimeFunction('dayofweek', 'Luxifer\DQL\Datetime\DayOfWeek');
        $config->addCustomDatetimeFunction('dayofyear', 'Luxifer\DQL\Datetime\DayOfYear');
        $config->addCustomDatetimeFunction('hour', 'Luxifer\DQL\Datetime\Hour');
        $config->addCustomDatetimeFunction('minute', 'Luxifer\DQL\Datetime\Minute');
        $config->addCustomDatetimeFunction('month', 'Luxifer\DQL\Datetime\Month');
        $config->addCustomDatetimeFunction('quarter', 'Luxifer\DQL\Datetime\Quarter');
        $config->addCustomDatetimeFunction('second', 'Luxifer\DQL\Datetime\Second');
        $config->addCustomDatetimeFunction('time', 'Luxifer\DQL\Datetime\Time');
        $config->addCustomDatetimeFunction('year', 'Luxifer\DQL\Datetime\Year');
    }

    /**
     * @dataProvider partsProvider
     */
    public function testDateParts($part)
    {
        $query = $this->em->createQuery(
            sprintf("SELECT %s('2003-12-31 01:02:03') FROM %s", strtoupper($part), self::FAKE_ENTITY)
        );

        $this->assertEquals(
            $query->getSQL(),
            sprintf("SELECT %s('2003-12-31 01:02:03') AS sclr0 FROM some_fake s0_", strtoupper($part))
        );
    }

    /**
     * Data provider
     */
    public function partsProvider()
    {
        return [
            ['date'],
            ['month'],
            ['dayofmonth'],
            ['dayofweek'],
            ['dayofyear'],
            ['quarter'],
        ];
    }
}
