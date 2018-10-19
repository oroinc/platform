<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\DashboardBundle\Form\EventListener\WidgetItemsFormSubscriber;
use Symfony\Component\Form\FormEvent;

class WidgetItemsFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    protected $widgetConfigs;
    protected $widgetItemsFormSubscriber;

    public function setUp()
    {
        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->widgetConfigs = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetConfigs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetItemsFormSubscriber = new WidgetItemsFormSubscriber($this->widgetConfigs, $translator);
    }

    public function testPreSetShouldPrepareDataForForm()
    {
        $twigVariables = [
            'widgetDataItems' => [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
        ];

        $eventData = null;

        $expectedEventData = [
            'items' => [
                [
                    'id'    => 'revenue',
                    'label' => 'Revenue',
                    'show'  => true,
                    'order' => 1,
                ],
                [
                    'id'    => 'orders_number',
                    'label' => 'Orders number',
                    'show'  => true,
                    'order' => 2,
                ],
            ],
        ];

        $this->widgetConfigs->expects($this->once())
            ->method('getWidgetAttributesForTwig')
            ->with('big_numbers_widget')
            ->will($this->returnValue($twigVariables));

        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('widget_name')
            ->will($this->returnValue('big_numbers_widget'));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $event = new FormEvent($form, $eventData);
        $this->widgetItemsFormSubscriber->preSet($event);

        $this->assertEquals($expectedEventData, $event->getData());
    }

    public function testPreSetShouldUpdateStoredData()
    {
        $twigVariables = [
            'widgetDataItems' => [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
        ];

        $eventData = [
            'items' => [
                [
                    'id'    => 'revenue',
                    'label' => 'Revenue old label',
                    'show'  => false,
                    'order' => 2,
                ],
                [
                    'id'    => 'orders_number',
                    'label' => 'Orders number old label',
                    'show'  => true,
                    'order' => 1,
                ],
            ],
        ];

        $expectedEventData = [
            'items' => [
                [
                    'id'    => 'orders_number',
                    'label' => 'Orders number',
                    'show'  => true,
                    'order' => 1,
                ],
                [
                    'id'    => 'revenue',
                    'label' => 'Revenue',
                    'show'  => false,
                    'order' => 2,
                ],
            ],
        ];

        $this->widgetConfigs->expects($this->once())
            ->method('getWidgetAttributesForTwig')
            ->with('big_numbers_widget')
            ->will($this->returnValue($twigVariables));

        $formConfig = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('widget_name')
            ->will($this->returnValue('big_numbers_widget'));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $event = new FormEvent($form, $eventData);
        $this->widgetItemsFormSubscriber->preSet($event);

        $this->assertEquals($expectedEventData, $event->getData());
    }
}
