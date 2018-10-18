<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Config\Shared\JsonApi\FixFieldNaming;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class FixFieldNamingTest extends ConfigProcessorTestCase
{
    /** @var FixFieldNaming */
    private $processor;

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

    public function testProcessWithUnknownIdentifierAndFieldNamedId()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id' => null
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
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => null
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
                'name' => null,
                'id'   => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'      => [
                        'property_path' => 'name'
                    ],
                    'classId' => [
                        'property_path' => 'id'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithIdentifierNotNamedIdAndHasFieldNamedIdButDoesNotHaveIdentifierField()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'id' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['name'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'id'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenFieldNamedIdIsPartOfIdentifier()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id', 'id1'],
            'fields'                 => [
                'id'  => null,
                'id1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['classId', 'id1'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'id'
                    ],
                    'id1'     => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenFieldNamedIdIsPartOfIdentifierAndHasPropertyPath()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id', 'id1'],
            'fields'                 => [
                'id'  => [
                    'property_path' => 'realId'
                ],
                'id1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['classId', 'id1'],
                'fields'                 => [
                    'classId' => [
                        'property_path' => 'realId'
                    ],
                    'id1'     => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWithCompositeIdentifierWhenNoFieldNamedIdInIdentifier()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id1', 'id2'],
            'fields'                 => [
                'id1' => null,
                'id2' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id1', 'id2'],
                'fields'                 => [
                    'id1' => null,
                    'id2' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenExistsFieldNamedType()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classType' => [
                        'property_path' => 'type'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenIdentifierFieldNamedIdHasPropertyPath()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['id'],
            'fields'                 => [
                'id' => [
                    'property_path' => 'realId'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id' => [
                        'property_path' => 'realId'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFieldNamedIdHasPropertyPath()
    {
        $config = [
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'name' => null,
                'id'   => [
                    'property_path' => 'realId'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy'       => 'all',
                'identifier_field_names' => ['id'],
                'fields'                 => [
                    'id'      => [
                        'property_path' => 'name'
                    ],
                    'classId' => [
                        'property_path' => 'realId'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenFieldNamedTypeHasPropertyPath()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'type' => [
                    'property_path' => 'realType'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'classType' => [
                        'property_path' => 'realType'
                    ]
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
            'exclusion_policy'       => 'all',
            'identifier_field_names' => ['name'],
            'fields'                 => [
                'id'      => null,
                'classId' => null,
                'name'    => null
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
                'classType' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }
}
