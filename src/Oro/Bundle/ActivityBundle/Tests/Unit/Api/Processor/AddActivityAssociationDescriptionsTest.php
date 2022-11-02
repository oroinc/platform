<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ActivityBundle\Api\ActivityAssociationProvider;
use Oro\Bundle\ActivityBundle\Api\Processor\AddActivityAssociationDescriptions;
use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AddActivityAssociationDescriptionsTest extends ConfigProcessorTestCase
{
    /** @var ActivityAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityAssociationProvider;

    /** @var ResourceDocParserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $docParser;

    /** @var AddActivityAssociationDescriptions */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityAssociationProvider = $this->createMock(ActivityAssociationProvider::class);
        $this->docParser = $this->createMock(ResourceDocParserInterface::class);

        $resourceDocParserProvider = $this->createMock(ResourceDocParserProvider::class);
        $resourceDocParserProvider->expects(self::any())
            ->method('getResourceDocParser')
            ->with($this->context->getRequestType())
            ->willReturn($this->docParser);

        $entityNameProvider = $this->createMock(EntityNameProvider::class);
        $entityNameProvider->expects(self::any())
            ->method('getEntityName')
            ->with(self::isType('string'))
            ->willReturnCallback(function (string $className) {
                return strtolower(substr($className, strrpos($className, '\\') + 1)) . ' (description)';
            });
        $entityNameProvider->expects(self::any())
            ->method('getEntityPluralName')
            ->with(self::isType('string'))
            ->willReturnCallback(function (string $className) {
                return strtolower(substr($className, strrpos($className, '\\') + 1)) . ' (plural description)';
            });

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with(self::isType('string'), DataType::ENTITY_TYPE, $this->context->getRequestType())
            ->willReturnCallback(function (string $className) {
                return strtolower(substr($className, strrpos($className, '\\') + 1));
            });

        $this->processor = new AddActivityAssociationDescriptions(
            $this->activityAssociationProvider,
            $valueNormalizer,
            $resourceDocParserProvider,
            $entityNameProvider
        );
    }

    public function testProcessWhenNoTargetAction(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::never())
            ->method('isActivityEntity');
        $this->activityAssociationProvider->expects(self::never())
            ->method('getActivityAssociations');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessWhenTargetActionIsOptions(): void
    {
        $entityClass = 'Test\Entity';
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::never())
            ->method('isActivityEntity');
        $this->activityAssociationProvider->expects(self::never())
            ->method('getActivityAssociations');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction(ApiAction::OPTIONS);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForActivityEntity(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
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

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_targets_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%activity_entity%', '%activity_targets_association%', null)
            ->willReturn('Description for "%activity_entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type'   => 'association:manyToMany:activity',
                        'description' => 'Description for "entity (description)".'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForActivityEntityWhenActivityTargetsAssociationAlreadyHasDescription(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'activityTargets' => [
                    'data_type'   => 'association:manyToMany:activity',
                    'description' => 'Existing description.'
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

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getFieldDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type'   => 'association:manyToMany:activity',
                        'description' => 'Existing description.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForActivityEntityWhenActivityTargetsAssociationDoesNotExist(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($entityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getFieldDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessForActivityEntityThatHasAssociationToAnotherActivity(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'activityTargets' => [
                    'data_type' => 'association:manyToMany:activity'
                ],
                'activityFirst'   => [
                    'target_class' => 'Test\Activity1',
                    'data_type'    => 'unidirectionalAssociation:association1'
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
            ->willReturn(
                [
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::exactly(2))
            ->method('registerDocumentationResource')
            ->withConsecutive(
                ['@OroActivityBundle/Resources/doc/api/activity_targets_association.md'],
                ['@OroActivityBundle/Resources/doc/api/activity_association.md']
            );
        $this->docParser->expects(self::exactly(2))
            ->method('getFieldDocumentation')
            ->willReturnMap([
                [
                    '%activity_entity%',
                    '%activity_targets_association%',
                    null,
                    'Description for "%activity_entity_name%".'
                ],
                [
                    '%activity_target_entity%',
                    '%activity_association%',
                    null,
                    'Description for "%activity_entity_plural_name%" associated with the "%entity_name%".'
                ]
            ]);

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityTargets' => [
                        'data_type'   => 'association:manyToMany:activity',
                        'description' => 'Description for "entity (description)".'
                    ],
                    'activityFirst'   => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for "activity1 (plural description)"'
                            . ' associated with the "entity (description)".'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'activityFirst' => [
                    'target_class' => 'Test\Activity1',
                    'data_type'    => 'unidirectionalAssociation:association1'
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
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%activity_target_entity%', '%activity_association%', null)
            ->willReturn('Description for "%activity_entity_plural_name%" associated with the "%entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityFirst' => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Description for "activity1 (plural description)"'
                            . ' associated with the "entity (description)".'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociationsWhenAssociationAlreadyHasDescription(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
        $definition = $this->createConfigObject([
            'fields' => [
                'activityFirst' => [
                    'target_class' => 'Test\Activity1',
                    'data_type'    => 'unidirectionalAssociation:association1',
                    'description'  => 'Existing description.'
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
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%activity_target_entity%', '%activity_association%', null)
            ->willReturn('Description for "%activity_entity_plural_name%" associated with the "%entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'activityFirst' => [
                        'target_class' => 'Test\Activity1',
                        'data_type'    => 'unidirectionalAssociation:association1',
                        'description'  => 'Existing description.'
                    ]
                ]
            ],
            $definition
        );
    }

    public function testProcessForEntityWithActivityAssociationsWhenAssociationDoesNotExist(): void
    {
        $entityClass = 'Test\Entity';
        $targetAction = ApiAction::UPDATE;
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
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_association.md');
        $this->docParser->expects(self::once())
            ->method('getFieldDocumentation')
            ->with('%activity_target_entity%', '%activity_association%', null)
            ->willReturn('Description for "%activity_entity_plural_name%" associated with the "%entity_name%".');

        $this->context->setClassName($entityClass);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessSubresourceForActivityEntity(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'activityTargets';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityTargetClasses')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['Test\Target1', 'Test\Target2']);

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_targets_association.md');
        $this->docParser->expects(self::once())
            ->method('getSubresourceDocumentation')
            ->with('%activity_entity%', '%activity_targets_association%', $targetAction)
            ->willReturn('Documentation for "%activity_entity_name%" (target: "%activity_target_entity_type%").');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Documentation for "parent (description)" (target: "target1").'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForActivityEntityWhenNoActivityTargetClasses(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'activityTargets';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityTargetClasses')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_targets_association.md');
        $this->docParser->expects(self::once())
            ->method('getSubresourceDocumentation')
            ->with('%activity_entity%', '%activity_targets_association%', $targetAction)
            ->willReturn('Documentation for "%activity_entity_name%" (target: "%activity_target_entity_type%").');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Documentation for "parent (description)" (target: "users").'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForActivityEntityWhenItAlreadyHasDocumentation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'activityTargets';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([
            'documentation' => 'Existing documentation.'
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);
        $this->activityAssociationProvider->expects(self::never())
            ->method('getActivityTargetClasses');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Existing documentation.'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForActivityEntityForNotActivityTargetsSubresource(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'another';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(true);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);
        $this->activityAssociationProvider->expects(self::never())
            ->method('getActivityTargetClasses');

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }

    public function testProcessSubresourceForEntityWithActivityAssociations(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'activityFirst';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::once())
            ->method('registerDocumentationResource')
            ->with('@OroActivityBundle/Resources/doc/api/activity_association.md');
        $this->docParser->expects(self::once())
            ->method('getSubresourceDocumentation')
            ->with('%activity_target_entity%', '%activity_association%', $targetAction)
            ->willReturn(
                'Documentation for "%activity_entity_plural_name%" associated with the "%entity_name%"'
                . ' (target: "%activity_entity_type%").'
            );

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Documentation for "activity1 (plural description)"'
                    . ' associated with the "parent (description)" (target: "activity1").'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForEntityWithActivityAssociationsWhenItAlreadyHasDocumentation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'activityFirst';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([
            'documentation' => 'Existing documentation.'
        ]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'documentation' => 'Existing documentation.'
            ],
            $definition
        );
    }

    public function testProcessSubresourceForEntityWithActivityAssociationsForNotActivityAssociation(): void
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\Parent';
        $associationName = 'another';
        $targetAction = ApiAction::UPDATE_RELATIONSHIP;
        $definition = $this->createConfigObject([]);

        $this->activityAssociationProvider->expects(self::once())
            ->method('isActivityEntity')
            ->with($parentEntityClass)
            ->willReturn(false);
        $this->activityAssociationProvider->expects(self::once())
            ->method('getActivityAssociations')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(
                [
                    'activityFirst' => ['className' => 'Test\Activity1', 'associationName' => 'association1']
                ]
            );

        $this->docParser->expects(self::never())
            ->method('registerDocumentationResource');
        $this->docParser->expects(self::never())
            ->method('getSubresourceDocumentation');

        $this->context->setClassName($entityClass);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setResult($definition);
        $this->context->setTargetAction($targetAction);
        $this->processor->process($this->context);

        $this->assertConfig([], $definition);
    }
}
