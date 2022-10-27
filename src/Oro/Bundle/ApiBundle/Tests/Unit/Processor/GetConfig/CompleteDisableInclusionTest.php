<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDisableInclusion;

class CompleteDisableInclusionTest extends ConfigProcessorTestCase
{
    /** @var CompleteDisableInclusion */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CompleteDisableInclusion();
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResult()->hasDisableInclusion());
    }

    public function testProcessWhenNoFields()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => []
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResult()->hasDisableInclusion());
    }

    public function testProcessWhenDisableInclusionIsAlreadySet()
    {
        $config = [
            'exclusion_policy'  => 'all',
            'disable_inclusion' => false,
            'fields'            => [
                'association1' => [
                    'data_type'        => 'integer',
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\AssociationTarget',
                    'fields'           => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertTrue($this->context->getResult()->hasDisableInclusion());
        self::assertTrue($this->context->getResult()->isInclusionEnabled());
    }

    public function testProcessWhenEntityHasAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type'        => 'integer',
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\AssociationTarget',
                    'fields'           => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResult()->hasDisableInclusion());
        self::assertTrue($this->context->getResult()->isInclusionEnabled());
    }

    public function testProcessWhenEntityHasAssociationButItIsExcluded()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type'        => 'integer',
                    'exclude'          => true,
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\AssociationTarget',
                    'fields'           => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertTrue($this->context->getResult()->hasDisableInclusion());
        self::assertFalse($this->context->getResult()->isInclusionEnabled());
    }

    public function testProcessWhenEntityHasAssociationButItIsReturnedAsField()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'data_type'        => 'object',
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\AssociationTarget',
                    'fields'           => [
                        'id' => null
                    ]
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertTrue($this->context->getResult()->hasDisableInclusion());
        self::assertFalse($this->context->getResult()->isInclusionEnabled());
    }

    public function testProcessWhenEntityDoesNotHaveAssociations()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => [
                    'data_type' => 'integer'
                ]
            ]
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertTrue($this->context->getResult()->hasDisableInclusion());
        self::assertFalse($this->context->getResult()->isInclusionEnabled());
    }

    public function testProcessForMultiTargetEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'id' => [
                    'data_type' => 'string'
                ]
            ]
        ];

        $this->context->setClassName(EntityIdentifier::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResult()->hasDisableInclusion());
        self::assertTrue($this->context->getResult()->isInclusionEnabled());
    }
}
