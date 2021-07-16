<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ActionBundle\Model\AbstractGuesser;
use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractGuesserTest extends \PHPUnit\Framework\TestCase
{
    /* @var AbstractGuesser */
    protected $guesser;

    /* @var MockObject|FormRegistry */
    protected $formRegistry;

    /* @var MockObject|ManagerRegistry */
    protected $managerRegistry;

    /* @var MockObject|ConfigProvider */
    protected $entityConfigProvider;

    /* @var MockObject|ConfigProvider */
    protected $formConfigProvider;

    /* @var DoctrineTypeMappingProvider|MockObject */
    protected $doctrineTypeMappingProvider;

    protected function setUp(): void
    {
        $this->formRegistry = $this->createMock(FormRegistry::class);
        $this->managerRegistry = $this->getMockForAbstractClass(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrineTypeMappingProvider = $this->createMock(DoctrineTypeMappingProvider::class);

        $this->guesser = $this->getMockBuilder(AbstractGuesser::class)
            ->setConstructorArgs([
                $this->formRegistry,
                $this->managerRegistry,
                $this->entityConfigProvider,
                $this->formConfigProvider
            ])
            ->getMockForAbstractClass();

        $this->guesser->setDoctrineTypeMappingProvider($this->doctrineTypeMappingProvider);
    }

    public function testGuessMetadataAndFieldNoEntityManagerException()
    {
        $this->expectException(\Oro\Bundle\ActionBundle\Exception\AttributeException::class);
        $this->expectExceptionMessage("Can't get entity manager for class RootClass");

        $rootClass = 'RootClass';

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($rootClass)
            ->will($this->returnValue(null));

        $this->guesser->guessMetadataAndField($rootClass, 'entity.field');
    }

    public function testAddDoctrineTypeMapping()
    {
        $doctrineType = 'date';
        $attributeType = 'object';
        $attributeOptions = ['class' => 'DateTime'];

        $this->guesser->setDoctrineTypeMappingProvider(null);
        $this->guesser->addDoctrineTypeMapping($doctrineType, $attributeType, $attributeOptions);

        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';
        $fieldLabel = 'Field Label';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(static::any())->method('getName')->willReturn($rootClass);
        $metadata->expects(static::any())->method('hasAssociation')->with('field')->willReturn(false);
        $metadata->expects(static::any())->method('hasField')->with('field')->willReturn(true);
        $metadata->expects(static::any())->method('getTypeOfField')->with('field')->willReturn('date');

        $this->setEntityMetadata([$rootClass => $metadata]);
        $this->setEntityConfigProvider($rootClass, 'field', false, true, $fieldLabel);

        $this->assertAttributeOptions(
            $this->guesser->guessParameters($rootClass, $propertyPath),
            $fieldLabel,
            'object',
            ['class' => 'DateTime']
        );
    }

    public function testGuessMetadataAndFieldOneElement()
    {
        $this->assertNull($this->guesser->guessMetadataAndField('TestEntity', 'single_element_path'));
        $this->assertNull($this->guesser->guessMetadataAndField('TestEntity', new PropertyPath('single_element_path')));
    }

    public function testGuessMetadataAndFieldInvalidComplexPath()
    {
        $propertyPath = 'entity.unknown_field';
        $rootClass = 'RootClass';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())->method('hasAssociation')->with('unknown_field')
            ->will($this->returnValue(false));
        $metadata->expects($this->once())->method('hasField')->with('unknown_field')
            ->will($this->returnValue(false));

        $this->setEntityMetadata([$rootClass => $metadata]);

        $this->assertNull($this->guesser->guessMetadataAndField($rootClass, $propertyPath));
    }

    public function testGuessMetadataAndFieldAssociationField()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));

        $this->setEntityMetadata([$rootClass => $metadata]);

        $this->assertEquals(
            ['metadata' => $metadata, 'field' => 'association'],
            $this->guesser->guessMetadataAndField($rootClass, $propertyPath)
        );
    }

    public function testGuessMetadataAndFieldSecondLevelAssociation()
    {
        $propertyPath = 'entity.association.field';
        $rootClass = 'RootClass';
        $associationEntity = 'AssociationEntity';

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects($this->any())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));
        $entityMetadata->expects($this->once())->method('getAssociationTargetClass')->with('association')
            ->will($this->returnValue($associationEntity));

        $associationMetadata = $this->createMock(ClassMetadata::class);
        $associationMetadata->expects($this->once())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $associationMetadata->expects($this->once())->method('hasField')->with('field')
            ->will($this->returnValue(true));

        $this->setEntityMetadata([$rootClass => $entityMetadata, $associationEntity => $associationMetadata]);

        $this->assertEquals(
            ['metadata' => $associationMetadata, 'field' => 'field'],
            $this->guesser->guessMetadataAndField($rootClass, $propertyPath)
        );
    }

    public function testGuessParametersNoMetadataAndFieldGuess()
    {
        $this->assertNull($this->guesser->guessParameters('TestEntity', 'single_element_path'));
    }

    public function testGuessParametersFieldWithoutMapping()
    {
        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('hasField')->with('field')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getTypeOfField')->with('field')
            ->will($this->returnValue('not_existing_type'));

        $this->setEntityMetadata([$rootClass => $metadata]);

        $this->doctrineTypeMappingProvider->expects($this->any())
            ->method('getDoctrineTypeMappings')->willReturn([]);

        $this->assertNull($this->guesser->guessParameters($rootClass, $propertyPath));
    }

    public function testGuessParametersFieldWithMapping()
    {
        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';
        $fieldLabel = 'Field Label';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));
        $metadata->expects($this->any())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('hasField')->with('field')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getTypeOfField')->with('field')
            ->will($this->returnValue('date'));

        $this->setEntityMetadata([$rootClass => $metadata]);
        $this->setEntityConfigProvider($rootClass, 'field', false, true, $fieldLabel);

        $this->doctrineTypeMappingProvider->expects($this->any())
            ->method('getDoctrineTypeMappings')->willReturn(
                [
                    'date' => [
                        'type' => 'object',
                        'options' => ['class' => 'DateTime']
                    ]
                ]
            );

        $this->assertAttributeOptions(
            $this->guesser->guessParameters($rootClass, $propertyPath),
            $fieldLabel,
            'object',
            ['class' => 'DateTime']
        );
    }

    public function testGuessParametersSingleAssociation()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';
        $associationClass = 'AssociationClass';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));
        $metadata->expects($this->any())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('hasField')->with('association')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('isCollectionValuedAssociation')->with('association')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('getAssociationTargetClass')->with('association')
            ->will($this->returnValue($associationClass));

        $this->setEntityMetadata([$rootClass => $metadata]);
        $this->setEntityConfigProvider($rootClass, 'association', false, true, null, 'ref-one');

        $this->doctrineTypeMappingProvider->expects($this->any())
            ->method('getDoctrineTypeMappings')->willReturn([]);

        $result = $this->guesser->guessParameters($rootClass, $propertyPath);
        $this->assertAttributeOptions(
            $result,
            null,
            'entity',
            ['class' => $associationClass]
        );
    }

    public function testGuessParametersFieldFromEntityConfig()
    {
        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';
        $fieldLabel = 'Field Label';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));

        $metadata->expects($this->any())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('hasField')->with('field')
            ->will($this->returnValue(false));

        $this->setEntityMetadata([$rootClass => $metadata]);
        $this->setEntityConfigProvider($rootClass, 'field', false, true, $fieldLabel, 'date');

        $this->doctrineTypeMappingProvider->expects($this->any())
            ->method('getDoctrineTypeMappings')->willReturn(
                [
                    'date' => [
                        'type' => 'object',
                        'options' => ['class' => 'DateTime']
                    ]
                ]
            );

        $this->assertAttributeOptions(
            $this->guesser->guessParameters($rootClass, $propertyPath),
            $fieldLabel,
            'object',
            ['class' => 'DateTime']
        );
    }

    public function testGuessParametersCollectionAssociation()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));
        $metadata->expects($this->any())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('hasField')->with('association')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('isCollectionValuedAssociation')->with('association')
            ->will($this->returnValue(true));

        $this->setEntityMetadata([$rootClass => $metadata]);
        $this->setEntityConfigProvider($rootClass, 'association', true, false);

        $this->doctrineTypeMappingProvider->expects($this->any())
            ->method('getDoctrineTypeMappings')->willReturn([]);

        $this->assertAttributeOptions(
            $this->guesser->guessParameters($rootClass, $propertyPath),
            null,
            'object',
            ['class' => ArrayCollection::class]
        );
    }

    /**
     * @param array       $actualOptions
     * @param string|null $label
     * @param string      $type
     * @param array       $options
     */
    protected function assertAttributeOptions($actualOptions, $label, $type, array $options = [])
    {
        $this->assertNotNull($actualOptions);
        $this->assertIsArray($actualOptions);
        $this->assertArrayHasKey('label', $actualOptions);
        $this->assertArrayHasKey('type', $actualOptions);
        $this->assertArrayHasKey('options', $actualOptions);
        $this->assertEquals($label, $actualOptions['label']);
        $this->assertEquals($type, $actualOptions['type']);
        $this->assertEquals($options, $actualOptions['options']);
    }

    protected function setEntityMetadata(array $metadataArray)
    {
        $valueMap = [];
        foreach ($metadataArray as $entity => $metadata) {
            $valueMap[] = [$entity, $metadata];
        }

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->isType('string'))
            ->will($this->returnValueMap($valueMap));

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with($this->isType('string'))
            ->will($this->returnValue($entityManager));
    }

    /**
     * @param string      $class
     * @param string      $field
     * @param bool        $multiple
     * @param bool        $hasConfig
     * @param string|null $label
     * @param string|null $fieldType
     */
    protected function setEntityConfigProvider(
        $class,
        $field,
        $multiple = false,
        $hasConfig = true,
        $label = null,
        $fieldType = null
    ) {
        $labelOption = $multiple ? 'plural_label' : 'label';

        $entityConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $entityConfig->expects($this->any())->method('has')->with($labelOption)
            ->will($this->returnValue(!empty($label)));
        $entityConfig->expects($this->any())->method('get')->with($labelOption)
            ->will($this->returnValue($label));

        $this->entityConfigProvider->expects($this->any())->method('hasConfig')->with($class, $field)
            ->will($this->returnValue($hasConfig));
        $this->entityConfigProvider->expects($this->any())->method('getConfig')->with($class, $field)
            ->will($this->returnValue($entityConfig));

        if ($fieldType) {
            $configId = $this->getMockBuilder(FieldConfigId::class)
                ->disableOriginalConstructor()
                ->getMock();
            $entityConfig->expects($this->any())->method('getId')
                ->will($this->returnValue($configId));
            $configId->expects($this->any())->method('getFieldType')
                ->will($this->returnValue($fieldType));
        }
    }
}
