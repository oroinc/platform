<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\CompoundObjectType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityScalarCollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityType;
use Oro\Bundle\ApiBundle\Form\Type\NestedAssociationType;
use Oro\Bundle\ApiBundle\Form\Type\ScalarCollectionType;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;

class MetadataTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_CLASS    = 'Test\Entity';
    private const TEST_PROPERTY = 'testField';

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var MetadataTypeGuesser */
    private $typeGuesser;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->typeGuesser = new MetadataTypeGuesser(
            [
                'integer'  => ['integer', []],
                'datetime' => ['test_datetime', ['model_timezone' => 'UTC', 'view_timezone' => 'UTC']]
            ],
            $this->doctrineHelper
        );
    }

    /**
     * @param EntityMetadata|null $metadata
     *
     * @return MetadataAccessorInterface
     */
    protected function getMetadataAccessor(EntityMetadata $metadata = null)
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

    /**
     * @param string                      $className
     * @param EntityDefinitionConfig|null $config
     *
     * @return ConfigAccessorInterface
     */
    protected function getConfigAccessor($className, EntityDefinitionConfig $config = null)
    {
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

    /**
     * @param string $fieldName
     * @param string $dataType
     *
     * @return FieldMetadata
     */
    protected function createFieldMetadata($fieldName, $dataType)
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName($fieldName);
        $fieldMetadata->setDataType($dataType);

        return $fieldMetadata;
    }

    /**
     * @param string $associationName
     * @param string $targetClass
     * @param bool   $isCollection
     * @param string $dataType
     *
     * @return AssociationMetadata
     */
    protected function createAssociationMetadata($associationName, $targetClass, $isCollection, $dataType)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithoutOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'integer'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess('integer', [], TypeGuess::HIGH_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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

    public function testGuessTypeForToOneAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
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
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForArrayAssociationForNotManageableEntity()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
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

    public function testGuessTypeForArrayAssociationForManageableEntity()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
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

    public function testGuessTypeForCollapsedArrayAssociationWithoutTargetMetadata()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWithoutChildFieldsAndAssociations()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertNull(
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationForNotManageableEntity()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                ScalarCollectionType::class,
                ['entry_data_class' => 'Test\TargetEntity', 'entry_data_property' => 'name'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationForManageableEntity()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
        $targetMetadata->addField($this->createFieldMetadata('name', 'string'));
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                EntityScalarCollectionType::class,
                ['entry_data_class' => 'Test\TargetEntity', 'entry_data_property' => 'name'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForCollapsedArrayAssociationWhenChildPropertyIsAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $associationMetadata->setCollapsed();
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
        $targetMetadata->addAssociation(
            $this->createAssociationMetadata('association1', 'Test\TargetEntity1', false, 'integer')
        );
        $associationMetadata->setTargetMetadata($targetMetadata);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        self::assertEquals(
            new TypeGuess(
                EntityScalarCollectionType::class,
                ['entry_data_class' => 'Test\TargetEntity', 'entry_data_property' => 'association1'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForAssociationContainsNestedObject()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'array'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
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

    public function testGuessTypeForNestedAssociation()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $associationMetadata = $this->createAssociationMetadata(
            self::TEST_PROPERTY,
            'Test\TargetEntity',
            true,
            'integer'
        );
        $metadata->addAssociation($associationMetadata);

        $targetMetadata = new EntityMetadata();
        $targetMetadata->setClassName('Test\TargetEntity');
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
