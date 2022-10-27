<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ActivityBundle\Api\ActivityAssociationProvider;
use Oro\Bundle\ActivityBundle\Api\Processor\AddActivityAssociations;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;

class AddActivityAssociationsTest extends ConfigProcessorTestCase
{
    /** @var ActivityAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityAssociationProvider;

    /** @var AddActivityAssociations */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityAssociationProvider = $this->createMock(ActivityAssociationProvider::class);

        $this->processor = new AddActivityAssociations($this->activityAssociationProvider);
    }

    public function testProcessForActivityEntity(): void
    {
        $entityClass = 'Test\Activity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type' => 'association:manyToMany:activity'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForActivityEntityWhenActivityTargetsAssociationIsDisabled(): void
    {
        $entityClass = 'Test\Activity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityTargets' => [
                    'exclude' => true
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type' => 'association:manyToMany:activity',
                        'exclude'   => true
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForActivityEntityWhenActivityTargetsConfiguredManually(): void
    {
        $entityClass = 'Test\Activity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityTargets' => [
                    'data_type' => 'association:manyToMany:activity'
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type' => 'association:manyToMany:activity'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForActivityEntityWhenActivityTargetsAssociationReconfiguredToUseAnotherDataType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The association "activityTargets" cannot be added to "Test\Activity"'
            . ' because an association with this name already exists.'
        );

        $entityClass = 'Test\Activity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityTargets' => [
                    'data_type' => 'int'
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::never())
            ->method('getActivityAssociations');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);
    }

    public function testProcessForActivityEntityThatHasAssociationToAnotherActivity(): void
    {
        $entityClass = 'Test\Activity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityAnother' => ['className' => 'Test\AnotherActivity', 'associationName' => 'association1']
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type' => 'association:manyToMany:activity'
                    ],
                    'activityAnother' => [
                        'target_class' => 'Test\AnotherActivity',
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithoutActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForEntityWithActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst'  => ['className' => 'Test\Activity1', 'associationName' => 'association1'],
                    'activitySecond' => ['className' => 'Test\Activity2', 'associationName' => 'association2']
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityFirst'  => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ],
                    'activitySecond' => [
                        'target_class' => 'Test\Activity2',
                        'data_type'    => 'unidirectionalAssociation:association2'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociationsWhenSomeAssociationsAreDisabled(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityFirst' => [
                    'exclude' => true
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst'  => ['className' => 'Test\Activity1', 'associationName' => 'association1'],
                    'activitySecond' => ['className' => 'Test\Activity2', 'associationName' => 'association2']
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityFirst'  => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'exclude'      => true
                    ],
                    'activitySecond' => [
                        'target_class' => 'Test\Activity2',
                        'data_type'    => 'unidirectionalAssociation:association2'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociationsWhenSomeAssociationsConfiguredManually(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityFirst' => [
                    'data_type' => 'unidirectionalAssociation:association1'
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst'  => ['className' => 'Test\Activity1', 'associationName' => 'association1'],
                    'activitySecond' => ['className' => 'Test\Activity2', 'associationName' => 'association2']
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityFirst'  => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1'
                    ],
                    'activitySecond' => [
                        'target_class' => 'Test\Activity2',
                        'data_type'    => 'unidirectionalAssociation:association2'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociationsWhenSomeHaveReconfiguredToUseAnotherDataType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The activity association "activityFirst" cannot be added to "Test\Entity"'
            . ' because an association with this name already exists.'
            . ' To rename the association to the "Test\Activity1" activity entity'
            . ' use "oro_activity.api.activity_association_names" configuration option.'
            . ' For example:' . "\n"
            . 'oro_activity:' . "\n"
            . '    api:' . "\n"
            . '        activity_association_names:' . "\n"
            . '            \'Test\Activity1\': \'newName\''
        );

        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([
            'fields' => [
                'activityFirst' => [
                    'data_type' => 'int'
                ]
            ]
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst'  => ['className' => 'Test\Activity1', 'associationName' => 'association1'],
                    'activitySecond' => ['className' => 'Test\Activity2', 'associationName' => 'association2']
                ]
            );

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);
    }
}
