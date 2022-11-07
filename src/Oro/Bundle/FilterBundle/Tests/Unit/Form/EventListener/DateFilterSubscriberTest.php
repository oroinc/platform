<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubmitContext;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\FilterBundle\Utils\DateFilterModifier;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateFilterSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private const TIMEZONE = 'Asia/Tokyo';

    private DateFilterSubscriber $subscriber;

    protected function setUp(): void
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $providerMock = $this->createMock(DateModifierProvider::class);
        $providerMock->expects(self::any())
            ->method('getVariableKey')
            ->willReturnCallback(function () {
                return DateModifierInterface::LABEL_VAR_PREFIX . DateModifierInterface::VAR_THIS_YEAR;
            });

        $modifier = new DateFilterModifier(
            new Compiler(new Lexer($translatorMock, $providerMock), new Parser($localeSettings))
        );

        $localeSettings->expects(self::any())
            ->method('getTimezone')
            ->willReturn(self::TIMEZONE);

        $this->subscriber = new DateFilterSubscriber($modifier);
    }

    private function getUtcDate(string $dateTime): string
    {
        return (new \DateTime($dateTime, new \DateTimeZone('UTC')))->format('Y-m-d H:i:00\Z');
    }

    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SUBMIT => 'preSubmit',
                FormEvents::SUBMIT => 'submit'
            ],
            DateFilterSubscriber::getSubscribedEvents()
        );
    }

    public function testPreSubmitWithCustomFormTimeZoneConfig(): void
    {
        $data = ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 12:00:00']];
        $submitContext = new DateFilterSubmitContext();
        $form = $this->createMock(FormInterface::class);
        $valueForm = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $event = new FormEvent($form, $data);

        $form->expects(self::any())
            ->method('get')
            ->with('value')
            ->willReturn($valueForm);
        $form->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->willReturnMap([['submit_context', null, $submitContext]]);
        $valueForm->expects(self::any())
            ->method('all')
            ->willReturn(['start' => 'start subform']);

        $this->subscriber->preSubmit($event);

        self::assertEquals(
            ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 12:00:00Z']],
            $event->getData()
        );
        self::assertEquals(
            ['value' => ['start_original' => $data['value']['start']]],
            $submitContext->applyValues([])
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testPreSubmit(
        array $data,
        array $expectedData,
        array $valueSubforms = [],
        array $shouldAddFields = []
    ): void {
        $submitContext = new DateFilterSubmitContext();
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $valueForm = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $data);

        $form->expects(self::any())
            ->method('get')
            ->with('value')
            ->willReturn($valueForm);
        $form->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->willReturnMap([['submit_context', null, $submitContext]]);
        $valueForm->expects(self::any())
            ->method('all')
            ->willReturn($valueSubforms);
        $valueForm->expects(self::exactly(count($shouldAddFields)))
            ->method('add');

        $this->subscriber->preSubmit($event);
        // should process only once, do not break expectation
        $this->subscriber->preSubmit($event);

        self::assertEquals($expectedData, $event->getData());

        $expectedSubmitContextData = [];
        if (isset($data['value']['start'])) {
            $expectedSubmitContextData['value']['start_original'] = $data['value']['start'];
        }
        if (isset($data['value']['end'])) {
            $expectedSubmitContextData['value']['end_original'] = $data['value']['end'];
        }
        self::assertEquals($expectedSubmitContextData, $submitContext->applyValues([]));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        $weekDateTime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $weekDateTime->modify('this week');
        // Needed because Oro\Bundle\FilterBundle\Expression\Date\ExpressionResult changes first day of week
        $weekNumber = $weekDateTime->format('W');

        $yearDateTime = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $yearNumber = $yearDateTime->format('Y');
        return [
            'should process with custom time zone' => [
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01']],
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'value' => [
                        'start' => $this->getUtcDate('2001-01-01')
                    ]
                ],
                ['start' => 'start subform']
            ],
            'should process date value' => [
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01']],
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'value' => [
                        'start' => $this->getUtcDate('2001-01-01')
                    ]
                ],
                ['start' => 'start subform']
            ],
            'should process day of week' => [
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process weeks' => [
                [
                    'part' => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 3, 'end' => sprintf('{{%d}}', DateModifierInterface::VAR_THIS_WEEK)]
                ],
                [
                    'part' => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 3, 'end' => $weekNumber]
                ],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process months' => [
                [
                    'part' => DateModifierInterface::PART_MONTH,
                    'value' => ['start' => 3, 'end' => null]
                ],
                ['part' => DateModifierInterface::PART_MONTH, 'value' => ['start' => 3, 'end' => null]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process quarters' => [
                [
                    'part' => DateModifierInterface::PART_QUARTER,
                    'value' => ['start' => 3, 'end' => '2001-12-31']
                ],
                ['part' => DateModifierInterface::PART_QUARTER, 'value' => ['start' => 3, 'end' => 4]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process years' => [
                [
                    'part' => DateModifierInterface::PART_YEAR,
                    'value' => ['start' => 2001, 'end' => 2014]
                ],
                ['part' => DateModifierInterface::PART_YEAR, 'value' => ['start' => 2001, 'end' => 2014]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process days' => [
                [
                    'part' => DateModifierInterface::PART_DAY,
                    'value' => ['start' => 1, 'end' => 12]
                ],
                ['part' => DateModifierInterface::PART_DAY, 'value' => ['start' => 1, 'end' => 12]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process day of year' => [
                [
                    'part' => DateModifierInterface::PART_DOY,
                    'value' => ['start' => 23]
                ],
                ['part' => DateModifierInterface::PART_DOY, 'value' => ['start' => 23]],
                ['start' => 'start subform'],
                ['start' => null]
            ],
            'should process date start of the year value with equals' => [
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'type' => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_SOY . '}}']
                ],
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'type' => AbstractDateFilterType::TYPE_BETWEEN,
                    'value' => [
                        'start' => $yearNumber . '-01-01 00:00',
                        'end' => $yearNumber . '-01-02 00:00'
                    ]
                ]
            ],
            'should process date start of the year value with not equals' => [
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'type' => AbstractDateFilterType::TYPE_NOT_EQUAL,
                    'value' => ['end' => '{{' . DateModifierInterface::VAR_SOY . '}}']
                ],
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'type' => AbstractDateFilterType::TYPE_NOT_BETWEEN,
                    'value' => [
                        'end' => $yearNumber . '-01-02 00:00',
                        'start' => '{{' . DateModifierInterface::VAR_SOY . '}}'
                    ]
                ]
            ],
            'should change part to "value" with "this day without year" variable' => [
                [
                    'part' => DateModifierInterface::PART_MONTH,
                    'type' => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_THIS_DAY_W_Y . '}}']
                ],
                [
                    'part' => DateModifierInterface::PART_VALUE,
                    'type' => AbstractDateFilterType::TYPE_EQUAL,
                    'value' => ['start' => '{{' . DateModifierInterface::VAR_THIS_DAY_W_Y . '}}']
                ]
            ],
        ];
    }

    public function testSubmitWithNullData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $form->expects(self::never())
            ->method('getConfig');

        $this->subscriber->submit($event);

        self::assertNull($event->getData());
    }

    public function testSubmitWithoutStoredSubmittedValues(): void
    {
        $data = ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 12:00:00Z']];
        $submitContext = new DateFilterSubmitContext();
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $event = new FormEvent($form, $data);

        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('submit_context')
            ->willReturn($submitContext);

        $this->subscriber->submit($event);

        self::assertEquals($data, $event->getData());
    }

    public function testSubmitWithStoredSubmittedValues(): void
    {
        $submittedStartValue = '2001-01-01 12:00:00';
        $data = ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 12:00:00Z']];
        $submitContext = new DateFilterSubmitContext();
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $event = new FormEvent($form, $data);

        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);
        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('submit_context')
            ->willReturn($submitContext);

        $submitContext->addValue('start_original', $submittedStartValue);

        $this->subscriber->submit($event);

        $expectedData = $data;
        $expectedData['value']['start_original'] = $submittedStartValue;
        self::assertEquals($expectedData, $event->getData());
    }
}
