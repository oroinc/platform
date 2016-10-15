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

    public function testDescriptionForCreatedAtField()
    {
        $config = [
            'fields' => [
                'id'        => null,
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
                    'createdAt' => [
                        'description' => 'The date and time of resource record creation'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionForCreatedAtFieldWhenItAlreadyHasDescription()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'createdAt' => [
                    'description' => 'existing description'
                ],
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'createdAt' => [
                        'description' => 'existing description'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionForUpdatedAtField()
    {
        $config = [
            'fields' => [
                'id'        => null,
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
                    'created'   => null,
                    'updatedAt' => [
                        'description' => 'The date and time of the last update of the resource record'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testDescriptionForUpdatedAtFieldWhenItAlreadyHasDescription()
    {
        $config = [
            'fields' => [
                'id'        => null,
                'updatedAt' => [
                    'description' => 'existing description'
                ],
            ]
        ];

        $this->context->setTargetAction('get_list');
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'updatedAt' => [
                        'description' => 'existing description'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testWithNoTargetAction()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'        => null,
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
                    'createdAt' => null,
                    'updatedAt' => null,
                ]
            ],
            $this->context->getResult()
        );
    }
}
