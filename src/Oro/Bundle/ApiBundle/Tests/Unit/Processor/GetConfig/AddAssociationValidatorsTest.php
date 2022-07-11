<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\AddAssociationValidators;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderInterface;
use Oro\Bundle\ApiBundle\Provider\AssociationAccessExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover;

class AddAssociationValidatorsTest extends ConfigProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AssociationAccessExclusionProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $associationAccessExclusionProviderRegistry;

    /** @var AddAssociationValidators */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->associationAccessExclusionProviderRegistry =
            $this->createMock(AssociationAccessExclusionProviderRegistry::class);

        $this->processor = new AddAssociationValidators(
            $this->doctrineHelper,
            $this->associationAccessExclusionProviderRegistry
        );
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'association1' => [
                    'target_class' => 'Test\Association1Target'
                ],
                'association2' => [
                    'target_class' => 'Test\Association2Target',
                    'target_type'  => 'to-many',
                    'form_options' => ['test_option' => 'test_value']
                ],
                'association3' => [
                    'target_class'  => 'Test\Association3Target',
                    'target_type'   => 'to-many',
                    'property_path' => 'realAssociation3'
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->associationAccessExclusionProviderRegistry->expects(self::never())
            ->method('getAssociationAccessExclusionProvider');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull($configObject->getField('field1')->getFormOptions());
        self::assertNull($configObject->getField('association1')->getFormOptions());
        self::assertEquals(
            [
                'test_option' => 'test_value',
                'constraints' => [
                    new HasAdderAndRemover([
                        'class'    => self::TEST_CLASS_NAME,
                        'property' => 'association2',
                        'groups'   => ['api']
                    ])
                ]
            ],
            $configObject->getField('association2')->getFormOptions()
        );
        self::assertEquals(
            [
                'constraints' => [
                    new HasAdderAndRemover([
                        'class'    => self::TEST_CLASS_NAME,
                        'property' => 'realAssociation3',
                        'groups'   => ['api']
                    ])
                ]
            ],
            $configObject->getField('association3')->getFormOptions()
        );
    }

    public function testProcessToManyAssociationForNotManageableEntityWhenConstraintAlreadyExists()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'groups' => [
                    'target_class' => Group::class,
                    'target_type'  => 'to-many'
                ]
            ]
        ];

        $existingConstraint = new HasAdderAndRemover([
            'class'    => User::class,
            'property' => 'groups',
            'groups'   => ['api']
        ]);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $configObject->getField('groups')->addFormConstraint($existingConstraint);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(UserProfile::class)
            ->willReturn(false);

        $this->associationAccessExclusionProviderRegistry->expects(self::never())
            ->method('getAssociationAccessExclusionProvider');

        $this->context->setResult($configObject);
        $this->context->setClassName(UserProfile::class);
        $this->processor->process($this->context);

        self::assertEquals(
            ['constraints' => [$existingConstraint]],
            $configObject->getField('groups')->getFormOptions()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessForManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'       => null,
                'association1' => null,
                'association2' => [
                    'form_options' => ['test_option' => 'test_value']
                ],
                'association3' => [
                    'property_path' => 'realAssociation3'
                ],
                'association4' => null
            ]
        ];

        $entityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $entityMetadata->expects(self::exactly(5))
            ->method('hasAssociation')
            ->willReturnMap([
                ['field1', false],
                ['association1', true],
                ['association2', true],
                ['realAssociation3', true],
                ['association4', true]
            ]);
        $entityMetadata->expects(self::exactly(3))
            ->method('isCollectionValuedAssociation')
            ->willReturnMap([
                ['association1', false],
                ['association2', true],
                ['realAssociation3', true]
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($entityMetadata);

        $associationAccessExclusionProvider = $this->createMock(AssociationAccessExclusionProviderInterface::class);
        $this->associationAccessExclusionProviderRegistry->expects(self::once())
            ->method('getAssociationAccessExclusionProvider')
            ->with($this->context->getRequestType())
            ->willReturn($associationAccessExclusionProvider);
        $associationAccessExclusionProvider->expects(self::exactly(4))
            ->method('isIgnoreAssociationAccessCheck')
            ->willReturnMap([
                [self::TEST_CLASS_NAME, 'association1', false],
                [self::TEST_CLASS_NAME, 'association2', false],
                [self::TEST_CLASS_NAME, 'realAssociation3', false],
                [self::TEST_CLASS_NAME, 'association4', true]
            ]);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull($configObject->getField('field1')->getFormOptions());
        self::assertEquals(
            [
                'constraints' => [new AccessGranted(['groups' => ['api']])]
            ],
            $configObject->getField('association1')->getFormOptions()
        );
        self::assertEquals(
            [
                'test_option' => 'test_value',
                'constraints' => [
                    new HasAdderAndRemover([
                        'class'    => self::TEST_CLASS_NAME,
                        'property' => 'association2',
                        'groups'   => ['api']
                    ]),
                    new All(new AccessGranted(['groups' => ['api']]))
                ]
            ],
            $configObject->getField('association2')->getFormOptions()
        );
        self::assertEquals(
            [
                'constraints' => [
                    new HasAdderAndRemover([
                        'class'    => self::TEST_CLASS_NAME,
                        'property' => 'realAssociation3',
                        'groups'   => ['api']
                    ]),
                    new All(new AccessGranted(['groups' => ['api']]))
                ]
            ],
            $configObject->getField('association3')->getFormOptions()
        );
        self::assertNull(
            $configObject->getField('association4')->getFormOptions()
        );
    }

    public function testProcessForByReferenceCollectionValuedAssociationOfNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class' => 'Test\Association1Target',
                    'target_type'  => 'to-many',
                    'form_options' => ['by_reference' => true]
                ],
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->associationAccessExclusionProviderRegistry->expects(self::never())
            ->method('getAssociationAccessExclusionProvider');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'by_reference' => true
            ],
            $configObject->getField('association1')->getFormOptions()
        );
    }

    public function testProcessForByReferenceCollectionValuedAssociationOfManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'form_options' => ['by_reference' => true]
                ]
            ]
        ];

        $entityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $entityMetadata->expects(self::once())
            ->method('hasAssociation')
            ->with('association1')
            ->willReturn(true);
        $entityMetadata->expects(self::once())
            ->method('isCollectionValuedAssociation')
            ->with('association1')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($entityMetadata);

        $associationAccessExclusionProvider = $this->createMock(AssociationAccessExclusionProviderInterface::class);
        $this->associationAccessExclusionProviderRegistry->expects(self::once())
            ->method('getAssociationAccessExclusionProvider')
            ->with($this->context->getRequestType())
            ->willReturn($associationAccessExclusionProvider);
        $associationAccessExclusionProvider->expects(self::once())
            ->method('isIgnoreAssociationAccessCheck')
            ->with(self::TEST_CLASS_NAME, 'association1')
            ->willReturn(false);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                'by_reference' => true,
                'constraints'  => [
                    new All(new AccessGranted(['groups' => ['api']]))
                ]
            ],
            $configObject->getField('association1')->getFormOptions()
        );
    }

    public function testProcessForComputedCollectionValuedAssociationOfNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'target_class'  => 'Test\Association1Target',
                    'target_type'   => 'to-many',
                    'property_path' => '_'
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->associationAccessExclusionProviderRegistry->expects(self::never())
            ->method('getAssociationAccessExclusionProvider');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull(
            $configObject->getField('association1')->getFormOptions()
        );
    }

    public function testProcessForComputedCollectionValuedAssociationOfManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'association1' => [
                    'property_path' => '_'
                ]
            ]
        ];

        $entityMetadata = $this->getClassMetadataMock(self::TEST_CLASS_NAME);
        $entityMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnMap([
                ['association1', true]
            ]);
        $entityMetadata->expects(self::any())
            ->method('isCollectionValuedAssociation')
            ->willReturnMap([
                ['association1', true]
            ]);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($entityMetadata);

        $associationAccessExclusionProvider = $this->createMock(AssociationAccessExclusionProviderInterface::class);
        $this->associationAccessExclusionProviderRegistry->expects(self::once())
            ->method('getAssociationAccessExclusionProvider')
            ->with($this->context->getRequestType())
            ->willReturn($associationAccessExclusionProvider);
        $associationAccessExclusionProvider->expects(self::never())
            ->method('isIgnoreAssociationAccessCheck');

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        self::assertNull(
            $configObject->getField('association1')->getFormOptions()
        );
    }
}
