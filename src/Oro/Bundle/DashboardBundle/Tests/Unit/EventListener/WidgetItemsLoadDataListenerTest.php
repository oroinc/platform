<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetItemsLoadDataEvent;
use Oro\Bundle\DashboardBundle\EventListener\WidgetItemsLoadDataListener;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class WidgetItemsLoadDataListenerTest extends \PHPUnit_Framework_TestCase
{
    private $widgetItemsLoadDataListener;

    public function setUp()
    {
        $this->widgetItemsLoadDataListener = new WidgetItemsLoadDataListener();
    }

    public function testFilterItemsByItemsChoice()
    {
        $expectedItems = [
            'revenue' => [
                'label' => 'Revenue',
            ],
        ];

        $items = [
            'revenue' => [
                'label' => 'Revenue',
            ],
            'orders_number' => [
                'label' => 'Orders number',
            ],
        ];

        $widgetConfig = [
            'configuration' => [
                'subWidgets' => [
                    'type' => 'oro_type_widget_items_choice',
                ],
            ]
        ];

        $options = [
            'subWidgets' => ['revenue']
        ];

        $event = new WidgetItemsLoadDataEvent($items, $widgetConfig, new WidgetOptionBag($options));
        $this->widgetItemsLoadDataListener->filterItemsByItemsChoice($event);
        $this->assertEquals($expectedItems, $event->getItems());
        $this->assertEquals(array_keys($expectedItems), array_keys($event->getItems()));
    }

    /**
     * @dataProvider filterItemsProvider
     */
    public function testFilterItems($items, $config, $expectedItems)
    {
        $widgetConfig = [
            'configuration' => [
                'subWidgets' => [],
            ]
        ];

        $event = new WidgetItemsLoadDataEvent($items, $widgetConfig, new WidgetOptionBag(['subWidgets' => $config]));
        $this->widgetItemsLoadDataListener->filterItems($event);
        $this->assertEquals($expectedItems, $event->getItems());
        $this->assertEquals(array_keys($expectedItems), array_keys($event->getItems()));
    }

    public function filterItemsProvider()
    {
        return [
            $this->getDataInOrder(),
            $this->getUnsortedData(),
            $this->getUnfilteredData(),
            $this->getMixedData(),
        ];
    }

    protected function getDataInOrder()
    {
        return [
            $items = [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
            $config = [
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
            ],
            $expectedItems = [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
        ];
    }

    protected function getUnsortedData()
    {
        return [
            $items = [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
            $config = [
                'items' => [
                    [
                        'id'    => 'revenue',
                        'label' => 'Revenue',
                        'show'  => true,
                        'order' => 2,
                    ],
                    [
                        'id'    => 'orders_number',
                        'label' => 'Orders number',
                        'show'  => true,
                        'order' => 1,
                    ],
                ],
            ],
            $expectedItems = [
                'orders_number' => [
                    'label' => 'Orders number',
                ],
                'revenue' => [
                    'label' => 'Revenue',
                ],
            ],
        ];
    }

    protected function getUnfilteredData()
    {
        return [
            $items = [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
            $config = [
                'items' => [
                    [
                        'id'    => 'revenue',
                        'label' => 'Revenue',
                        'show'  => false,
                        'order' => 1,
                    ],
                    [
                        'id'    => 'orders_number',
                        'label' => 'Orders number',
                        'show'  => true,
                        'order' => 2,
                    ],
                ],
            ],
            $expectedItems = [
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
        ];
    }

    protected function getMixedData()
    {
        return [
            $items = [
                'revenue' => [
                    'label' => 'Revenue',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
                'another' => [
                    'label' => 'Another',
                ],
            ],
            $config = [
                'items' => [
                    [
                        'id'    => 'revenue',
                        'label' => 'Revenue',
                        'show'  => false,
                        'order' => 3,
                    ],
                    [
                        'id'    => 'orders_number',
                        'label' => 'Orders number',
                        'show'  => true,
                        'order' => 2,
                    ],
                    [
                        'id'    => 'another',
                        'label' => 'Another',
                        'show'  => true,
                        'order' => 1,
                    ],
                ],
            ],
            $expectedItems = [
                'another' => [
                    'label' => 'Another',
                ],
                'orders_number' => [
                    'label' => 'Orders number',
                ],
            ],
        ];
    }
}
