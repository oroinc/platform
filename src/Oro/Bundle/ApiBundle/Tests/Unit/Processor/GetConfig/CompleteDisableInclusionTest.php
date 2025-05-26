<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDisableInclusion;

class CompleteDisableInclusionTest extends ConfigProcessorTestCase
{
    private CompleteDisableInclusion $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CompleteDisableInclusion();
    }

    public function testProcessForNotCompletedConfig(): void
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

    public function testProcessWhenNoFields(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => []
        ];

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResult()->hasDisableInclusion());
    }

    public function testProcessWhenDisableInclusionIsAlreadySet(): void
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

    public function testProcessWhenEntityHasAssociations(): void
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

    public function testProcessWhenEntityHasAssociationButItIsExcluded(): void
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

    public function testProcessWhenEntityHasAssociationButItIsReturnedAsField(): void
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

    public function testProcessWhenEntityDoesNotHaveAssociations(): void
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

    public function testProcessForMultiTargetEntity(): void
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
