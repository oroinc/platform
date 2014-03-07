<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;
use Oro\Bundle\FilterBundle\Expression\Date\Lexer;
use Oro\Bundle\FilterBundle\Expression\Date\Parser;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;

use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;

class DateFilterSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateFilterSubscriber */
    protected $subscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $compiler;

    public function setUp()
    {
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->setMethods(array('getTimezone'))
            ->getMock();
        $localeSettings->expects($this->any())
            ->method('getTimezone')
            ->will($this->returnValue('Europe/Moscow'));

        $translatorMock = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $providerMock   = $this->getMock('Oro\Bundle\FilterBundle\Provider\DateModifierProvider');
        $this->compiler   = new Compiler(new Lexer($translatorMock, $providerMock), new Parser($localeSettings));
        $this->subscriber = new DateFilterSubscriber($this->compiler);
    }

    public function testSubscribedEvents()
    {
        $events = DateFilterSubscriber::getSubscribedEvents();
        $this->assertCount(1, $events);

        $eventNames = array_keys($events);
        $this->assertEquals(FormEvents::PRE_SUBMIT, $eventNames[0]);
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
        $form      = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $valueForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $event     = new FormEvent($form, $data);

        $form->expects($this->any())->method('get')->with($this->equalTo('value'))
            ->will($this->returnValue($valueForm));
        $valueForm->expects($this->any())->method('all')->will($this->returnValue($valueSubforms));

        $valueForm->expects($this->exactly(count($shouldAddFields)))->method('add');

        $this->subscriber->preSubmit($event);
        // should process only once, do not break expectation
        $this->subscriber->preSubmit($event);

        $this->assertEquals($expectedData, $event->getData());
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'should process date value'  => [
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01']],
                ['part' => DateModifierInterface::PART_VALUE, 'value' => ['start' => '2001-01-01 00:00']],
                ['start' => 'start subform']
            ],
            'should process day of week' => [
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['part' => DateModifierInterface::PART_DOW, 'value' => ['start' => 2, 'end' => 5]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process weeks'       => [
                [
                    'part'  => DateModifierInterface::PART_WEEK,
                    'value' => ['start' => 3, 'end' => sprintf('{{%d}}', DateModifierInterface::VAR_THIS_WEEK)]
                ],
                ['part' => DateModifierInterface::PART_WEEK, 'value' => ['start' => 3, 'end' => date('W')]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process months'      => [
                [
                    'part'  => DateModifierInterface::PART_MONTH,
                    'value' => ['start' => 3, 'end' => null]
                ],
                ['part' => DateModifierInterface::PART_MONTH, 'value' => ['start' => 3, 'end' => null]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process quarters'    => [
                [
                    'part'  => DateModifierInterface::PART_QUARTER,
                    'value' => ['start' => 3, 'end' => '2001-12-31']
                ],
                ['part' => DateModifierInterface::PART_QUARTER, 'value' => ['start' => 3, 'end' => 4]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process years'       => [
                [
                    'part'  => DateModifierInterface::PART_YEAR,
                    'value' => ['start' => 2001, 'end' => 2014]
                ],
                ['part' => DateModifierInterface::PART_YEAR, 'value' => ['start' => 2001, 'end' => 2014]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process days'        => [
                [
                    'part'  => DateModifierInterface::PART_DAY,
                    'value' => ['start' => 1, 'end' => 12]
                ],
                ['part' => DateModifierInterface::PART_DAY, 'value' => ['start' => 1, 'end' => 12]],
                ['start' => 'start subform', 'end' => 'end subform'],
                ['start' => null, 'end' => null]
            ],
            'should process day of year' => [
                [
                    'part'  => DateModifierInterface::PART_DOY,
                    'value' => ['start' => 23]
                ],
                ['part' => DateModifierInterface::PART_DOY, 'value' => ['start' => 23]],
                ['start' => 'start subform'],
                ['start' => null]
            ]
        ];
    }
}
