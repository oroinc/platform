<?php

use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\AbstractTask;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Customer;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\RepeatableTask;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ScheduledTask;

return [
    'Oro\Bundle\DataBundle\Entity\Product'                               => [
        'alias'  => 'test_product',
        'mode'   => Mode::NORMAL,
        'label'  => 'test product',
        'fields' => [
            [
                'name'          => 'name',
                'target_type'   => 'string',
                'target_fields' => ['name', 'all_data']
            ],
            [
                'name'          => 'description',
                'target_type'   => 'string',
                'target_fields' => ['description', 'all_data']
            ],
            [
                'name'          => 'price',
                'target_type'   => 'decimal',
                'target_fields' => ['price']
            ],
            [
                'name'          => 'count',
                'target_type'   => 'integer',
                'target_fields' => ['count']
            ],
            [
                'name'          => 'createDate',
                'target_type'   => 'datetime',
                'target_fields' => ['create_date']
            ],
            [
                'name'            => 'manufacturer',
                'relation_type'   => 'to',
                'relation_fields' => [
                    [
                        'name'          => 'name',
                        'target_type'   => 'string',
                        'target_fields' => ['manufacturer', 'all_data']
                    ]
                ]
            ],
        ]
    ],
    'Oro\Bundle\DataBundle\Entity\Customer'                              => [
        'alias'  => 'test_customer',
        'label'  => 'test customer',
        'mode'   => Mode::NORMAL,
        'fields' => [],
    ],
    Customer::class         => [
        'alias'  => 'customer',
        'label'  => 'test customer',
        'mode'   => Mode::WITH_DESCENDANTS,
        'fields' => [],
    ],
    ConcreteCustomer::class => [
        'alias'  => 'concrete_customer',
        'label'  => 'test customer',
        'mode'   => Mode::NORMAL,
        'fields' => [],
    ],
    AbstractTask::class   => [
        'alias'  => 'task',
        'label'  => 'test task',
        'mode'   => Mode::ONLY_DESCENDANTS,
        'fields' => [],
    ],
    RepeatableTask::class => [
        'alias'  => 'repeatable_task',
        'label'  => 'repeatable_task',
        'mode'   => Mode::NORMAL,
        'fields' => [],
    ],
    ScheduledTask::class  => [
        'alias'  => 'scheduled_task',
        'label'  => 'scheduled_task',
        'mode'   => Mode::NORMAL,
        'fields' => [],
    ]
];
