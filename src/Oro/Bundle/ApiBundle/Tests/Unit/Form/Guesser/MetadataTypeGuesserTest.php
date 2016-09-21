<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\Guesser\MetadataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface;

class MetadataTypeGuesserTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS    = 'Test\Entity';
    const TEST_PROPERTY = 'testField';

    /** @var MetadataTypeGuesser */
    protected $typeGuesser;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeGuesser = new MetadataTypeGuesser(
            [
                'integer'  => ['integer', []],
                'datetime' => ['test_datetime', ['model_timezone' => 'UTC', 'view_timezone' => 'UTC']],
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
        $metadataAccessor = $this->getMock('Oro\Bundle\ApiBundle\Metadata\MetadataAccessorInterface');
        if (null === $metadata) {
            $metadataAccessor->expects($this->once())
                ->method('getMetadata')
                ->willReturn(null);
        } else {
            $metadataAccessor->expects($this->once())
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
        $configAccessor = $this->getMock('Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface');
        if (null === $config) {
            $configAccessor->expects($this->once())
                ->method('getConfig')
                ->willReturn(null);
        } else {
            $configAccessor->expects($this->once())
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

    public function testGuessRequired()
    {
        $this->assertNull($this->typeGuesser->guessRequired(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessMaxLength()
    {
        $this->assertNull($this->typeGuesser->guessMaxLength(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessPattern()
    {
        $this->assertNull($this->typeGuesser->guessPattern(self::TEST_CLASS, self::TEST_PROPERTY));
    }

    public function testGuessTypeWithoutMetadataAccessor()
    {
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeWithoutMetadata()
    {
        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor(null));
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForUndefinedField()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess('text', [], TypeGuess::LOW_CONFIDENCE),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }

    public function testGuessTypeForFormTypeWithoutOptions()
    {
        $metadata = new EntityMetadata();
        $metadata->setClassName(self::TEST_CLASS);
        $metadata->addField($this->createFieldMetadata(self::TEST_PROPERTY, 'integer'));

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
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
        $this->assertEquals(
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
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity',
                ['metadata' => $associationMetadata],
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
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity',
                ['metadata' => $associationMetadata],
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
        $this->assertNull(
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_collection',
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => 'oro_api_compound_entity',
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->typeGuesser->setConfigAccessor($this->getConfigAccessor(self::TEST_CLASS, $config));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity_collection',
                [
                    'entry_data_class' => 'Test\TargetEntity',
                    'entry_type'       => 'oro_api_compound_entity',
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
        $this->assertNull(
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
        $this->assertNull(
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_scalar_collection',
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity_scalar_collection',
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

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $this->typeGuesser->setMetadataAccessor($this->getMetadataAccessor($metadata));
        $this->assertEquals(
            new TypeGuess(
                'oro_api_entity_scalar_collection',
                ['entry_data_class' => 'Test\TargetEntity', 'entry_data_property' => 'association1'],
                TypeGuess::HIGH_CONFIDENCE
            ),
            $this->typeGuesser->guessType(self::TEST_CLASS, self::TEST_PROPERTY)
        );
    }
}
