<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\JsonApi\FixFieldNaming;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class FixFieldNamingTest extends ConfigProcessorTestCase
{
    /** @var FixFieldNaming */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new FixFieldNaming();
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all'
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithUnknownIdentifierFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNamedId()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id'   => null,
                'type' => null,
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
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNotNamedId()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['name'],
                'fields'                 => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifier()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id', 'id1'],
            'fields'                 => [
                'id'   => null,
                'type' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['classId', 'id1'],
                'fields'                 => [
                    'classId'   => [
                        'property_path' => 'id'
                    ],
                    'classType' => [
                        'property_path' => 'type'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenReservedFieldsHavePropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'   => [
                    'property_path' => 'realId'
                ],
                'type' => [
                    'property_path' => 'realType'
                ],
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classId'   => [
                        'property_path' => 'realId'
                    ],
                    'classType' => [
                        'property_path' => 'realType'
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "id" reserved word cannot be used as a field name and it cannot be renamed to "classId" because a field with this name already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenIdFieldWithGuessedNameAlreadyExists()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'      => null,
                'classId' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "type" reserved word cannot be used as a field name and it cannot be renamed to "classType" because a field with this name already exists.
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenTypeFieldWithGuessedNameAlreadyExists()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type'      => null,
                'classType' => null,
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }
}
