<?php

use Oro\Bundle\SearchBundle\Query\Mode;

return array(
    'Oro\Bundle\DataBundle\Entity\Product'                               => array(
        'alias'  => 'test_product',
        'mode'   => Mode::NORMAL,
        'label'  => 'test product',
        'fields' => array(
            array(
                'name'          => 'name',
                'target_type'   => 'string',
                'target_fields' => array('name', 'all_data')
            ),
            array(
                'name'          => 'description',
                'target_type'   => 'string',
                'target_fields' => array('description', 'all_data')
            ),
            array(
                'name'          => 'price',
                'target_type'   => 'decimal',
                'target_fields' => array('price')
            ),
            array(
                'name'          => 'count',
                'target_type'   => 'integer',
                'target_fields' => array('count')
            ),
            array(
                'name'          => 'createDate',
                'target_type'   => 'datetime',
                'target_fields' => array('create_date')
            ),
            array(
                'name'            => 'manufacturer',
                'relation_type'   => 'to',
                'relation_fields' => array(
                    array(
                        'name'          => 'name',
                        'target_type'   => 'string',
                        'target_fields' => array('manufacturer', 'all_data')
                    )
                )
            ),
        )
    ),
    'Oro\Bundle\DataBundle\Entity\Customer'                              => array(
        'alias'  => 'test_customer',
        'label'  => 'test customer',
        'mode'   => Mode::NORMAL,
        'fields' => array(),
    ),
    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Customer'         => array(
        'alias'  => 'customer',
        'label'  => 'test customer',
        'mode'   => Mode::WITH_DESCENDANTS,
        'fields' => array(),
    ),
    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer' => array(
        'alias'  => 'concrete_customer',
        'label'  => 'test customer',
        'mode'   => Mode::NORMAL,
        'fields' => array(),
    ),
    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\AbstractTask'   => array(
        'alias'  => 'task',
        'label'  => 'test task',
        'mode'   => Mode::ONLY_DESCENDANTS,
        'fields' => array(),
    ),
    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\RepeatableTask' => array(
        'alias'  => 'repeatable_task',
        'label'  => 'repeatable_task',
        'mode'   => Mode::NORMAL,
        'fields' => array(),
    ),
    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ScheduledTask'  => array(
        'alias'  => 'scheduled_task',
        'label'  => 'scheduled_task',
        'mode'   => Mode::NORMAL,
        'fields' => array(),
    )
);
