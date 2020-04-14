<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateFilterSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateFilterSubscriber */
    protected $subscriber;

    /** @var DateFilterModifier|\PHPUnit\Framework\MockObject\MockObject */
    protected $modifier;

    private const TIMEZONE = 'Asia/Tokyo';

    protected function setUp(): void
    {
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = self::createMock(LocaleSettings::class);
        $localeSettings
            ->expects(self::any())
            ->method('getTimezone')
            ->will(self::returnValue(self::TIMEZONE));

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translatorMock */
        $translatorMock = self::createMock(TranslatorInterface::class);

        /** @var DateModifierProvider|\PHPUnit\Framework\MockObject\MockObject $providerMock */
        $providerMock = self::createMock(DateModifierProvider::class);

        $this->modifier = new DateFilterModifier(
            new Compiler(new Lexer($translatorMock, $providerMock), new Parser($localeSettings))
        );
        $this->subscriber = new DateFilterSubscriber($this->modifier);
    }

    public function testSubscribedEvents()
    {
        $events = DateFilterSubscriber::getSubscribedEvents();
        self::assertCount(1, $events);

        $eventNames = array_keys($events);
        self::assertEquals(FormEvents::PRE_SUBMIT, $eventNames[0]);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $data
     * @param array $expectedData
     * @param array $valueSubforms
     * @param array $shouldAddFields
     */
    public function testPreSubmit(array $data, array $expectedData, $valueSubforms = [], $shouldAddFields = [])
    {
        $form      = self::createMock('Symfony\Component\Form\Test\FormInterface');
        $valueForm = self::createMock('Symfony\Component\Form\Test\FormInterface');
        $event     = new FormEvent($form, $data);

        $form->expects(self::any())->method('get')->with(self::equalTo('value'))->will(self::returnValue($valueForm));
        $valueForm->expects(self::any())->method('all')->will(self::returnValue($valueSubforms));
        $valueForm->expects(self::exactly(count($shouldAddFields)))->method('add');

        $this->subscriber->preSubmit($event);
        // should process only once, do not break expectation
        $this->subscriber->preSubmit($event);

        self::assertEquals($expectedData, $event->getData());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider()
    {
        $weekDateTime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $weekDateTime->modify('this week');
        // Needed because Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult changes first day of week
        $weekNumber = $weekDateTime->format('W');

        $yearDateTime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $yearNumber = $yearDateTime->format('Y');
        return [
            'should process date value'                                           => [
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01']],
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 00:00']],
                ['start' => 'start subform']
            ],
            'should process day of week'                                          => [
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process weeks'                                                => [
                [
                    'part'  => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 3, 'end' => sprintf('{{%d}}', DateModifierInterface::VAR_THIS_WEEK)]
                ],
                [
                    'part'  => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 3, 'end' => $weekNumber]
                ],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process months'                                               => [
                [
                    'part'  => DateModifierInterface::PART_MONTH,
                    'value' => ['start' => 3, 'end' => null]
                ],
                ['part' => DateModifierInterface::PART_MONTH, 'value' => ['start' => 3, 'end' => null]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process quarters'                                             => [
                [
                    'part'  => DateModifierInterface::PART_QUARTER,
                    'value' => ['start' => 3, 'end' => '2001-12-31']
                ],
                ['part' => DateModifierInterface::PART_QUARTER, 'value' => ['start' => 3, 'end' => 4]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process years'                                                => [
                [
                    'part'  => DateModifierInterface::PART_YEAR,
                    'value' => ['start' => 2001, 'end' => 2014]
                ],
                ['part' => DateModifierInterface::PART_YEAR, 'value' => ['start' => 2001, 'end' => 2014]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process days'                                                 => [
                [
                    'part'  => DateModifierInterface::PART_DAY,
                    'value' => ['start' => 1, 'end' => 12]
                ],
                ['part' => DateModifierInterface::PART_DAY, 'value' => ['start' => 1, 'end' => 12]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process day of year'                                          => [
                [
                    'part'  => DateModifierInterface::PART_DOY,
                    'value' => ['start' => 23]
                ],
                ['part' => DateModifierInterface::PART_DOY, 'value' => ['start' => 23]],
                ['start' => 'start subform'],
                ['start' => null]
            ],
            'should process date start of the year value with equals'             => [
                [
                    'part'  => DateModifierInterface::PART_VALUE,
                    'type'  => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_SOY . '}}']
                ],
                [
                    'part'  => DateModifierInterface::PART_VALUE,
                    'type'  => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => '{{' . DateModifierInterface::VAR_SOY . '}}',
                        'end' => $yearNumber . '-01-02 00:00'
                    ]
                ]
            ],
            'should process date start of the year value with not equals'         => [
                [
                    'part'  => DateModifierInterface::PART_VALUE,
                    'type'  => AbstractDateFilterType::TYPE_NOT_EQUAL,
                    'value' => ['end' => '{{' . DateModifierInterface::VAR_SOY . '}}']
                ],
                [
                    'part'  => DateModifierInterface::PART_VALUE,
                    'type'  => AbstractDateFilterType::TYPE_NOT_BETWEEN,
                    'value' => [
                        'end' => $yearNumber . '-01-02 00:00',
                        'start' => '{{' . DateModifierInterface::VAR_SOY . '}}'
                    ]
                ]
            ],
            'should change part to "value" with "this day without year" variable' => [
                [
                    'part'  => DateModifierInterface::PART_MONTH,
                    'type'  => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_THIS_DAY_W_Y . '}}']
                ],
                [
                    'part'  => DateModifierInterface::PART_VALUE,
                    'type'  => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_THIS_DAY_W_Y . '}}']
                ]
            ],
        ];
    }
}
