<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence\MonthNthStrategy;
use Oro\Bundle\CalendarBundle\Tools\Recurrence\NthStrategyHelper;
use Symfony\Component\Translation\TranslatorInterface;

class MonthNthStrategyTest extends \PHPUnit_Framework_TestCase
{
    /** @var MonthNthStrategy  */
    protected $strategy;

    protected function setUp()
    {
        $helper = new NthStrategyHelper();
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('transChoice')
            ->will(
                $this->returnCallback(
                    function ($id, $count, array $parameters = []) {
                        return $id;
                    }
                )
            );
        $translator->expects($this->any())
            ->method('trans')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        return $id;
                    }
                )
            );
        $dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new MonthNthStrategy($translator, $dateTimeFormatter);
        $this->strategy->setHelper($helper);
    }

    public function testGetName()
    {
        $this->assertEquals($this->strategy->getName(), 'recurrence_monthnth');
    }

    public function testSupports()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH);
        $this->assertTrue($this->strategy->supports($recurrence));

        $recurrence->setRecurrenceType('Test');
        $this->assertFalse($this->strategy->supports($recurrence));
    }

    public function testGetOccurrences()
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH)
            ->setInterval(2)
            ->setDayOfWeek(['monday'])
            ->setInstance(Recurrence::INSTANCE_FIRST)
            ->setStartTime(new \DateTime('2016-04-25'))
            ->setEndTime(new \DateTime('2016-06-10'));

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );

        $this->assertEquals([], $result);

        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-05-30'),
            new \DateTime('2016-07-03')
        );

        $this->assertEquals([new \DateTime('2016-06-06')], $result);

        $recurrence->setInstance(Recurrence::INSTANCE_LAST);
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-03-28'),
            new \DateTime('2016-05-01')
        );
        $this->assertEquals([new \DateTime('2016-04-25')], $result);

        $recurrence->setOccurrences(2);
        $recurrence->setEndTime(new \DateTime('2016-12-31'));
        $result = $this->strategy->getOccurrences(
            $recurrence,
            new \DateTime('2016-07-25'),
            new \DateTime('2016-09-04')
        );
        $this->assertEquals([], $result);
    }

    /**
     * @param $recurrenceData
     * @param $expected
     *
     * @dataProvider recurrencePatternsDataProvider
     */
    public function testGetRecurrencePattern($recurrenceData, $expected)
    {
        $recurrence = new Recurrence();
        $recurrence->setRecurrenceType(Recurrence::TYPE_MONTH_N_TH)
            ->setInterval($recurrenceData['interval'])
            ->setInstance($recurrenceData['instance'])
            ->setDayOfWeek($recurrenceData['dayOfWeek'])
            ->setStartTime(new \DateTime($recurrenceData['startTime']))
            ->setEndTime(new \DateTime($recurrenceData['endTime']))
            ->setOccurrences($recurrenceData['occurrences']);

        $this->assertEquals($expected, $this->strategy->getRecurrencePattern($recurrence));
    }

    /**
     * @return array
     */
    public function recurrencePatternsDataProvider()
    {
        return [
            'without_occurrences_and_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnth'
            ],
            'with_occurrences' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => Recurrence::MAX_END_DATE,
                    'occurrences' => 3,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnthoro.calendar.recurrence.patterns.occurrences'
            ],
            'with_end_date' => [
                'params' => [
                    'interval' => 2,
                    'instance' => 3,
                    'dayOfWeek' => ['sunday'],
                    'startTime' => '2016-04-28',
                    'endTime' => '2016-06-10',
                    'occurrences' => null,
                ],
                'expected' => 'oro.calendar.recurrence.patterns.monthnthoro.calendar.recurrence.patterns.end_date'
            ]
        ];
    }
}
