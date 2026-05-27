<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigureExtIdEntities;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigureExtIdEntitiesTest extends ConfigProcessorTestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ConfigureExtIdEntities $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new ConfigureExtIdEntities(
            [User::class => 'external_id'],
            $this->doctrineHelper
        );
    }

    public function testSomeCustomIdentifierIsAlreadyConfigured(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'identifier_field_names' => ['customId']
        ];

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityIdentifierFieldNamesForClass');

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testEntityIsNotManageable(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(false);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testApiResourceDoesNotUseIdentifierFromExternalSystem(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(UserProfile::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(UserProfile::class)
            ->willReturn($classMetadata);

        $this->context->setClassName(UserProfile::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testInheritanceMappingEntityApiResourceDoesNotUseIdentifierFromExternalSystem(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(Group::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(false);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(Group::class)
            ->willReturn($classMetadata);

        $this->context->setClassName(Group::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testEntityHasCompositeIdentifier(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id1', 'id2']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig($config, $this->context->getResult());
    }

    public function testApiResourceUsesIdentifierFromExternalSystem(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::never())
            ->method('isInheritanceTypeNone');
        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(User::class)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null,
                    'dbId' => [
                        'property_path' => 'id',
                        'form_options' => ['mapped' => false]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testInheritanceMappingEntityApiResourceUsesIdentifierFromExternalSystem(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(UserProfile::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('isInheritanceTypeNone')
            ->willReturn(false);
        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityMetadataForClass')
            ->with(UserProfile::class)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(UserProfile::class)
            ->willReturn(['id']);

        $this->context->setClassName(UserProfile::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null,
                    'dbId' => [
                        'property_path' => 'id',
                        'form_options' => ['mapped' => false]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testApiResourceUsesIdentifierFromExternalSystemAndOnlyIdRequested(): void
    {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setExtra(new FilterIdentifierFieldsConfigExtra());
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testApiResourceUsesIdentifierFromExternalSystemAndIdentifierFieldWasRenamed(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'externalId' => [
                    'property_path' => 'external_id'
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::never())
            ->method('isInheritanceTypeNone');
        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(User::class)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['externalId'],
                'fields' => [
                    'externalId' => [
                        'property_path' => 'external_id'
                    ],
                    'dbId' => [
                        'property_path' => 'id',
                        'form_options' => ['mapped' => false]
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @dataProvider dbIdFieldDescriptionDataProvider
     */
    public function testApiResourceUsesIdentifierFromExternalSystemAndDescriptionsExtra(
        string $targetAction,
        string $dbIdFieldDescription
    ): void {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::never())
            ->method('isInheritanceTypeNone');
        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(User::class)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setTargetAction($targetAction);
        $this->context->setExtra(new DescriptionsConfigExtra());
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null,
                    'dbId' => [
                        'property_path' => 'id',
                        'form_options' => ['mapped' => false],
                        'description' => $dbIdFieldDescription
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public static function dbIdFieldDescriptionDataProvider(): array
    {
        return [
            [
                ApiAction::GET,
                'A unique identifier in the database.'
            ],
            [
                ApiAction::CREATE,
                '<p>A unique identifier in the database.</p>'
                . '<p><strong>The read-only field. A passed value will be ignored.</strong></p>'
            ],
            [
                ApiAction::UPDATE,
                '<p>A unique identifier in the database.</p>'
                . '<p><strong>The read-only field. A passed value will be ignored.</strong></p>'
            ]
        ];
    }

    /**
     * @dataProvider targetActionDataProvider
     */
    public function testApiResourceWithoutIdGeneratorUsesIdentifierFromExternalSystemAndDescriptionsExtra(
        string $targetAction
    ): void {
        $config = ['exclusion_policy' => 'all'];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::never())
            ->method('isInheritanceTypeNone');
        $classMetadata->expects(self::once())
            ->method('usesIdGenerator')
            ->willReturn(false);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with(User::class)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setTargetAction($targetAction);
        $this->context->setExtra(new DescriptionsConfigExtra());
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null,
                    'dbId' => [
                        'property_path' => 'id',
                        'description' => 'A unique identifier in the database.'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public static function targetActionDataProvider(): array
    {
        return [
            [ApiAction::GET],
            [ApiAction::CREATE],
            [ApiAction::UPDATE]
        ];
    }

    public function testApiResourceUsesIdentifierFromExternalSystemAndWithConfiguredDbIdField(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'dbId' => [
                    'property_path' => 'some_id',
                    'description' => 'Some identifier.'
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(User::class)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityMetadataForClass');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityIdentifierFieldNamesForClass')
            ->with(User::class)
            ->willReturn(['id']);

        $this->context->setClassName(User::class);
        $this->context->setResult($this->createConfigObject($config));
        $this->context->setExtra(new DescriptionsConfigExtra());
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'identifier_field_names' => ['external_id'],
                'fields' => [
                    'external_id' => null,
                    'dbId' => [
                        'property_path' => 'some_id',
                        'description' => 'Some identifier.'
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
