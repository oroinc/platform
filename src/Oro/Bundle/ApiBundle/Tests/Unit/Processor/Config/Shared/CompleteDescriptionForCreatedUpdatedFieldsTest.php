<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDescriptionForCreatedUpdatedFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class CompleteDescriptionForCreatedUpdatedFieldsTest extends ConfigProcessorTestCase
{
    /** @var CompleteDescriptionForCreatedUpdatedFields */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new CompleteDescriptionForCreatedUpdatedFields();
    }

    public function testDescriptionForCreatedUpdateFields()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'name'      => null,
                'createdAt' => null,
                'updatedAt' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'name'      => null,
                    'createdAt' => [
                        'description' => CompleteDescriptionForCreatedUpdatedFields::CREATED_AT_DESCRIPTION
                    ],
                    'updatedAt' => [
                        'description' => CompleteDescriptionForCreatedUpdatedFields::UPDATED_AT_DESCRIPTION
                    ]
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionForCreatedField()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'name'      => null,
                'createdAt' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'name'      => null,
                    'createdAt' => [
                        'description' => CompleteDescriptionForCreatedUpdatedFields::CREATED_AT_DESCRIPTION
                    ]
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }

    public function testDescriptionForUpdateField()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'name'      => null,
                'created'   => null,
                'updatedAt' => null,
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'name'      => null,
                    'created'   => null,
                    'updatedAt' => [
                        'description' => CompleteDescriptionForCreatedUpdatedFields::UPDATED_AT_DESCRIPTION
                    ]
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
                'id'        => null,
                'name'      => null,
                'createdAt' => null,
                'updatedAt' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'        => null,
                    'name'      => null,
                    'createdAt' => null,
                    'updatedAt' => null,
                ]
            ],
            $this->context->getResult()->toArray()
        );
    }
}
