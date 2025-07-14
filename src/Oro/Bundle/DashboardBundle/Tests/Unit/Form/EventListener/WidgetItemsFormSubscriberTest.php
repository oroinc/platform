<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\DashboardBundle\Form\EventListener\WidgetItemsFormSubscriber;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetItemsFormSubscriberTest extends TestCase
{
    private WidgetConfigs&MockObject $widgetConfigs;
    private WidgetItemsFormSubscriber $widgetItemsFormSubscriber;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->widgetConfigs = $this->createMock(WidgetConfigs::class);

        $this->widgetItemsFormSubscriber = new WidgetItemsFormSubscriber($this->widgetConfigs, $translator);
    }

    public function testPreSetShouldPrepareDataForForm(): void
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
            ->willReturn($twigVariables);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('widget_name')
            ->willReturn('big_numbers_widget');

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $event = new FormEvent($form, $eventData);
        $this->widgetItemsFormSubscriber->preSet($event);

        $this->assertEquals($expectedEventData, $event->getData());
    }

    public function testPreSetShouldUpdateStoredData(): void
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
            ->willReturn($twigVariables);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->once())
            ->method('getOption')
            ->with('widget_name')
            ->willReturn('big_numbers_widget');

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $event = new FormEvent($form, $eventData);
        $this->widgetItemsFormSubscriber->preSet($event);

        $this->assertEquals($expectedEventData, $event->getData());
    }
}
