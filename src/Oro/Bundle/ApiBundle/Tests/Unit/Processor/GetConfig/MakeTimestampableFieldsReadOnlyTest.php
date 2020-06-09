<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Processor\GetConfig\MakeTimestampableFieldsReadOnly;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class MakeTimestampableFieldsReadOnlyTest extends ConfigProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MakeTimestampableFieldsReadOnly */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new MakeTimestampableFieldsReadOnly(
            $this->doctrineHelper
        );
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'exclusion_policy' => 'none',
            'fields'           => [
                'id'        => null,
                'createdAt' => null,
                'updatedAt' => null
            ]
        ];

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'id'        => null,
                    'createdAt' => null,
                    'updatedAt' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'createdAt' => null,
                'updatedAt' => null
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'createdAt' => null,
                    'updatedAt' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcess()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'createdAt' => null,
                'updatedAt' => null
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'createdAt' => [
                        'form_options' => [
                            'mapped' => false
                        ]
                    ],
                    'updatedAt' => [
                        'form_options' => [
                            'mapped' => false
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessForExcludedFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'createdAt' => [
                    'exclude' => true
                ],
                'updatedAt' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'createdAt' => [
                        'exclude' => true
                    ],
                    'updatedAt' => [
                        'exclude' => true
                    ]
                ]
            ],
            $configObject
        );
    }

    public function testProcessWhenFieldsManuallyMarkedAsNotReadOnly()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id'        => null,
                'createdAt' => [
                    'form_options' => [
                        'mapped' => true
                    ]
                ],
                'updatedAt' => [
                    'form_options' => [
                        'mapped' => true
                    ]
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'id'        => null,
                    'createdAt' => [
                        'form_options' => [
                            'mapped' => true
                        ]
                    ],
                    'updatedAt' => [
                        'form_options' => [
                            'mapped' => true
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }
}
