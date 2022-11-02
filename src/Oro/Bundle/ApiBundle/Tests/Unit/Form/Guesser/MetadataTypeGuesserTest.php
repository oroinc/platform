<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Guesser\DataTypeGuesser;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityType;
use Oro\Bundle\ApiBundle\Form\Type\NestedAssociationType;
use Oro\Bundle\ApiBundle\Form\Type\ScalarObjectType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetadataTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS = 'Test\Entity';
    private const TEST_PROPERTY = 'testField';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var MetadataTypeGuesser */
    private $typeGuesser;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->typeGuesser = new MetadataTypeGuesser(
            new DataTypeGuesser([
                'integer'  => ['integer', []],
                'datetime' => ['test_datetime', ['model_timezone' => 'UTC', 'view_timezone' => 'UTC']],
                'array'    => ['array', []]
            ]),
            $this->doctrineHelper
        );
    }

    private function getMetadataAccessor(EntityMetadata $metadata = null): MetadataAccessorInterface
    {
        $metadataAccessor = $this->createMock(MetadataAccessorInterface::class);
        if (null === $metadata) {
            $metadataAccessor->expects(self::once())
                ->method('getMetadata')
                ->willReturn(null);
        } else {
            $metadataAccessor->expects(self::once())
                ->method('getMetadata')
                ->with($metadata->getClassName())
                ->willReturn($metadata);
        }

        return $metadataAccessor;
    }

    private function getConfigAccessor(
        string $className,
        EntityDefinitionConfig $config = null
    ): ConfigAccessorInterface {
        $configAccessor = $this->createMock(ConfigAccessorInterface::class);
        if (null === $config) {
            $configAccessor->expects(self::once())
                ->method('getConfig')
                ->willReturn(null);
        } else {
            $configAccessor->expects(self::once())
                ->method('getConfig')
                ->with($className)
                ->willReturn($config);
        }

        return $configAccessor;
    }

    private function createFieldMetadata(string $fieldName, string $dataType): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);

        return $fieldMetadata;
    }

    private function createAssociationMetadata(
        string $associationName,
        string $targetClass,
        bool $isCollection,
        string $dataType
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata($associationName);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsCollection($isCollection);
        $associationMetadata->setDataType($dataType);

        return $associationMetadata;
    }

    public function testShouldGetPreviouslySetMetadataAccessor()
    {
        $metadataAccessor = $this->createMock(MetadataAccessorInterface::class);
        $this->typeGuesser->setMetadataAccessor($metadataAccessor);
        self::assertSame($metadataAccessor, $this->typeGuesser->getMetadataAccessor());
    }

    public function testShouldGetPreviouslySetConfigAccessor()
    {
        $configAccessor = $this->createMock(ConfigAccessorInterface::class);
        $this->typeGuesser->setConfigAccessor($configAccessor);
        self::assertSame($configAccessor, $this->typeGuesser->getConfigAccessor());
    }

    public function testGuessRequired()
    {
        self::assertNull($this->typeGuesser->guessRequired(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessMaxLength()
    {
        self::assertNull($this->typeGuesser->guessMaxLength(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessPattern()
    {
        self::assertNull($this->typeGuesser->guessPattern(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessTypeWithoutMetadataAccessor()
    {
        self::assertEquals(
            new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeWithoutMetadata()
    {
        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor(null));
        self::assertEquals(
            new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForUndefinedField()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithoutOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'integer'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess('integer', [], TypeGuess::HIGH_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'datetime'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                'test_datetime',
                ['model_timezone' => 'UTC', 'view_timezone' => 'UTC'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForNotMappedFieldType()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'string'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForArrayField()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'array'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess('array', [], TypeGuess::HIGH_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForTypedArrayField()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'currency[]'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess('array', [], TypeGuess::HIGH_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToOneAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                EntityType::class,
                [
                    'metadata'          => $associationMetadata,
                    'entity_mapper'     => null,
                    'included_entities' => null
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToOneAssociationWithIncludedEntities()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);
        $includedEntities = $this->createMock(IncludedEntityCollection::class);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setIncludedEntities($includedEntities);
        self::assertEquals(
            new TypeGuess(
                EntityType::class,
                [
                    'metadata'          => $associationMetadata,
                    'entity_mapper'     => null,
                    'included_entities' => $includedEntities
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                EntityType::class,
                [
                    'metadata'          => $associationMetadata,
                    'entity_mapper'     => null,
                    'included_entities' => null
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyAssociationWithIncludedEntities()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);
        $entityMapper = $this->createMock(EntityMapper::class);
        $includedEntities = $this->createMock(IncludedEntityCollection::class);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setEntityMapper($entityMapper);
        $this->typeGuesser->setIncludedEntities($includedEntities);
        self::assertEquals(
            new TypeGuess(
                EntityType::class,
                [
                    'metadata'          => $associationMetadata,
                    'entity_mapper'     => $entityMapper,
                    'included_entities' => $includedEntities
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForArrayAssociationWithoutTargetMetadata()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $config = new EntityDefinitionConfig();
        $config->addField(self::TEST_PROPERTY);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForArrayAssociationWithoutTargetConfig()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToOneArrayAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'data_class' => 'Test\TargetEntity',
                    'metadata'   => $targetMetadata,
                    'config'     => $associationConfig
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToOneArrayAssociationWithCustomFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationFieldConfig = $config->addField(self::TEST_PROPERTY);
        $associationFieldConfig->setFormOption('required', false);
        $associationFieldConfig->setFormOption('option1', 'value1');
        $associationConfig = $associationFieldConfig->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'data_class' => 'Test\TargetEntity',
                    'metadata'   => $targetMetadata,
                    'config'     => $associationConfig,
                    'required'   => false,
                    'option1'    => 'value1'
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyArrayAssociationForNotManageableEntity()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => CompoundObjectType::class,
                    'entry_options'    => [
                        'metadata' => $targetMetadata,
                        'config'   => $associationConfig
                    ]
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyArrayAssociationForManageableEntity()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                EntityCollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => CompoundObjectType::class,
                    'entry_options'    => [
                        'metadata' => $targetMetadata,
                        'config'   => $associationConfig
                    ]
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForToManyArrayAssociationWithCustomFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationFieldConfig = $config->addField(self::TEST_PROPERTY);
        $associationFieldConfig->setFormOption('option1', 'value1');
        $associationFieldConfig->setFormOption(
            'entry_options',
            ['required' => false, 'entry_option1' => 'value2']
        );
        $associationConfig = $associationFieldConfig->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => CompoundObjectType::class,
                    'entry_options'    => [
                        'metadata'      => $targetMetadata,
                        'config'        => $associationConfig,
                        'required'      => false,
                        'entry_option1' => 'value2'
                    ],
                    'option1'          => 'value1'
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWithoutTargetMetadata()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWithoutTargetConfig()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWithoutChildFieldsAndAssociations()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedToOneArrayAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                ScalarObjectType::class,
                [
                    'data_class'    => 'Test\TargetEntity',
                    'data_property' => 'name',
                    'metadata'      => $targetMetadata,
                    'config'        => $associationConfig
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedToOneArrayAssociationWithCustomFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationFieldConfig = $config->addField(self::TEST_PROPERTY);
        $associationFieldConfig->setFormOption('required', false);
        $associationFieldConfig->setFormOption('option1', 'value1');
        $associationConfig = $associationFieldConfig->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                ScalarObjectType::class,
                [
                    'data_class'    => 'Test\TargetEntity',
                    'data_property' => 'name',
                    'metadata'      => $targetMetadata,
                    'config'        => $associationConfig,
                    'required'      => false,
                    'option1'       => 'value1'
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedToManyArrayAssociationForNotManageableEntity()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => ScalarObjectType::class,
                    'entry_options'    => [
                        'data_property' => 'name',
                        'metadata'      => $targetMetadata,
                        'config'        => $associationConfig
                    ]
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedToManyArrayAssociationForManageableEntity()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                EntityCollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => ScalarObjectType::class,
                    'entry_options'    => [
                        'data_property' => 'name',
                        'metadata'      => $targetMetadata,
                        'config'        => $associationConfig
                    ]
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedToManyArrayAssociationWithCustomFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationFieldConfig = $config->addField(self::TEST_PROPERTY);
        $associationFieldConfig->setFormOption('option1', 'value1');
        $associationFieldConfig->setFormOption(
            'entry_options',
            ['required' => false, 'entry_option1' => 'value2']
        );
        $associationConfig = $associationFieldConfig->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => ScalarObjectType::class,
                    'entry_options'    => [
                        'data_property' => 'name',
                        'metadata'      => $targetMetadata,
                        'config'        => $associationConfig,
                        'required'      => false,
                        'entry_option1' => 'value2'
                    ],
                    'option1'          => 'value1'
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWhenChildPropertyIsAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $targetMetadata->addAssociation(
            $this->createAssociationMetadata('association1', 'Test\TargetEntity1', false, 'integer')
        );
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY)->getOrCreateTargetEntity();
        $associationConfig->addField('childField');

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                EntityCollectionType::class,
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => ScalarObjectType::class,
                    'entry_options'    => [
                        'data_property' => 'association1',
                        'metadata'      => $targetMetadata,
                        'config'        => $associationConfig
                    ]
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObject()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationConfig->setFormOptions(['data_class' => 'Test\TargetEntity']);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'data_class' => 'Test\TargetEntity',
                    'metadata'   => $targetMetadata,
                    'config'     => $associationTargetConfig
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObjectWithoutDataClassInFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationConfig->setFormOptions(['some_option' => 'option value']);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The form options for the "%s" field should contain the "data_class" option.',
            self::TEST_PROPERTY
        ));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY);
    }

    public function testGuessTypeForAssociationContainsNestedObjectWithoutDataClassInFormOptionsButWithInheritData()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationConfig->setFormOptions(['some_option' => 'option value', 'inherit_data' => true]);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'some_option'  => 'option value',
                    'inherit_data' => true,
                    'metadata'     => $targetMetadata,
                    'config'       => $associationTargetConfig
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObjectWithInheritDataAndNotMapped()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationConfig->setFormOptions(['inherit_data' => true, 'mapped' => false]);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'inherit_data'    => true,
                    'metadata'        => $targetMetadata,
                    'config'          => $associationTargetConfig,
                    'mapped'          => false,
                    'children_mapped' => false
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObjectWithNotInheritDataAndNotMapped()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationConfig->setFormOptions(['data_class' => 'Test\TargetEntity', 'mapped' => false]);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                CompoundObjectType::class,
                [
                    'data_class' => 'Test\TargetEntity',
                    'metadata'   => $targetMetadata,
                    'config'     => $associationTargetConfig,
                    'mapped'     => false
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObjectWithoutFormOptions()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            false,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedObject');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'The form options for the "%s" field should contain the "data_class" option.',
            self::TEST_PROPERTY
        ));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY);
    }

    public function testGuessTypeForNestedAssociation()
    {
        $metadata = new EntityMetadata(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $config = new EntityDefinitionConfig();
        $associationConfig = $config->addField(self::TEST_PROPERTY);
        $associationConfig->setDataType('nestedAssociation');
        $associationConfig->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $associationTargetConfig = $associationConfig->getOrCreateTargetEntity();
        $associationTargetConfig->addField('childField');

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        self::assertEquals(
            new TypeGuess(
                NestedAssociationType::class,
                [
                    'metadata' => $associationMetadata,
                    'config'   => $associationConfig
                ],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }
}
