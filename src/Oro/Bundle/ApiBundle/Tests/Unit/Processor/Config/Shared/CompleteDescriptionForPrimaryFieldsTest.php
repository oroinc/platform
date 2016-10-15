<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDescriptionForPrimaryFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class CompleteDescriptionForPrimaryFieldsTest extends ConfigProcessorTestCase
{
    /** @var CompleteDescriptionForPrimaryFields */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new CompleteDescriptionForPrimaryFields();
    }

    public function testDescriptionForPrimaryField()
    {
        $config = [
            'identifier_field_names' => ['id'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'     => null,
                'field1' => null,
                'field2' => null,
                'field3' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['id'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'     => [
                        'description' => CompleteDescriptionForPrimaryFields::ID_DESCRIPTION
                    ],
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionForPrimaryFieldWithDifferentName()
    {
        $config = [
            'identifier_field_names' => ['first_name'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'         => null,
                'first_name' => null,
                'field2'     => null,
                'field3'     => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['first_name'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'         => null,
                    'first_name' => [
                        'description' => CompleteDescriptionForPrimaryFields::ID_DESCRIPTION
                    ],
                    'field2'     => null,
                    'field3'     => null
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionNotSetForMultiplePrimaryField()
    {
        $config = [
            'identifier_field_names' => ['first_name', 'last_name'],
            'exclusion_policy'       => 'all',
            'fields'                 => [
                'id'         => null,
                'first_name' => null,
                'last_name'  => null,
                'field3'     => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'identifier_field_names' => ['first_name', 'last_name'],
                'exclusion_policy'       => 'all',
                'fields'                 => [
                    'id'         => null,
                    'first_name' => null,
                    'last_name'  => null,
                    'field3'     => null,
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testWithNoPrimaryField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'     => null,
                'field1' => null,
                'field2' => null,
                'field3' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testWithNoTargetAction()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'     => null,
                'field1' => null,
                'field2' => null,
                'field3' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'     => null,
                    'field1' => null,
                    'field2' => null,
                    'field3' => null
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }
}
