<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\AttributeGuesser;

class AttributeGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeGuesser
     */
    protected $guesser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formConfigProvider;

    protected function setUp()
    {
        $this->formRegistry = $this->getMockBuilder('Symfony\Component\Form\FormRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry
            = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ManagerRegistry');

        $this->entityConfigProvider
            = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->formConfigProvider
            = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->guesser = new AttributeGuesser(
            $this->formRegistry,
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider
        );
    }

    protected function tearDown()
    {
        unset($this->managerRegistry);
        unset($this->entityConfigProvider);
        unset($this->guesser);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Can't get entity manager for class RootClass
     */
    public function testGuessMetadataAndFieldNoEntityManagerException()
    {
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
        $attributeOptions = array('class' => 'DateTime');
        $expectedMapping = array($doctrineType => array('type' => $attributeType, 'options' => $attributeOptions));

        $this->guesser->addDoctrineTypeMapping($doctrineType, $attributeType, $attributeOptions);
        $this->assertAttributeEquals($expectedMapping, 'doctrineTypeMapping', $this->guesser);
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

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('hasAssociation')->with('unknown_field')
            ->will($this->returnValue(false));
        $metadata->expects($this->once())->method('hasField')->with('unknown_field')
            ->will($this->returnValue(false));

        $this->setEntityMetadata(array($rootClass => $metadata));

        $this->assertNull($this->guesser->guessMetadataAndField($rootClass, $propertyPath));
    }

    public function testGuessMetadataAndFieldAssociationField()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->once())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));

        $this->setEntityMetadata(array($rootClass => $metadata));

        $this->assertEquals(
            array('metadata' => $metadata, 'field' => 'association'),
            $this->guesser->guessMetadataAndField($rootClass, $propertyPath)
        );
    }

    public function testGuessMetadataAndFieldSecondLevelAssociation()
    {
        $propertyPath = 'entity.association.field';
        $rootClass = 'RootClass';
        $associationEntity = 'AssociationEntity';

        $entityMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $entityMetadata->expects($this->once())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));
        $entityMetadata->expects($this->once())->method('getAssociationTargetClass')->with('association')
            ->will($this->returnValue($associationEntity));

        $associationMetadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $associationMetadata->expects($this->once())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $associationMetadata->expects($this->once())->method('hasField')->with('field')
            ->will($this->returnValue(true));

        $this->setEntityMetadata(array($rootClass => $entityMetadata, $associationEntity => $associationMetadata));

        $this->assertEquals(
            array('metadata' => $associationMetadata, 'field' => 'field'),
            $this->guesser->guessMetadataAndField($rootClass, $propertyPath)
        );
    }

    public function testGuessAttributeParametersNoMetadataAndFieldGuess()
    {
        $this->assertNull($this->guesser->guessAttributeParameters('TestEntity', 'single_element_path'));
    }

    public function testGuessAttributeParametersFieldWithoutMapping()
    {
        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('hasField')->with('field')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getTypeOfField')->with('field')
            ->will($this->returnValue('not_existing_type'));

        $this->setEntityMetadata(array($rootClass => $metadata));

        $this->assertNull($this->guesser->guessAttributeParameters($rootClass, $propertyPath));
    }

    public function testGuessAttributeParametersFieldWithMapping()
    {
        $propertyPath = 'entity.field';
        $rootClass = 'RootClass';
        $fieldLabel = 'Field Label';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));
        $metadata->expects($this->any())->method('hasAssociation')->with('field')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('hasField')->with('field')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getTypeOfField')->with('field')
            ->will($this->returnValue('date'));

        $this->setEntityMetadata(array($rootClass => $metadata));
        $this->setEntityConfigProvider($rootClass, 'field', false, true, $fieldLabel);

        $this->guesser->addDoctrineTypeMapping('date', 'object', array('class' => 'DateTime'));

        $this->assertAttributeOptions(
            $this->guesser->guessAttributeParameters($rootClass, $propertyPath),
            $fieldLabel,
            'object',
            array('class' => 'DateTime')
        );
    }

    public function testGuessAttributeParametersSingleAssociation()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';
        $associationClass = 'AssociationClass';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
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

        $this->setEntityMetadata(array($rootClass => $metadata));
        $this->setEntityConfigProvider($rootClass, 'association', false, true);

        $this->assertAttributeOptions(
            $this->guesser->guessAttributeParameters($rootClass, $propertyPath),
            null,
            'entity',
            array('class' => $associationClass)
        );
    }

    public function testGuessAttributeParametersCollectionAssociation()
    {
        $propertyPath = 'entity.association';
        $rootClass = 'RootClass';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($rootClass));
        $metadata->expects($this->any())->method('hasAssociation')->with('association')
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('hasField')->with('association')
            ->will($this->returnValue(false));
        $metadata->expects($this->any())->method('isCollectionValuedAssociation')->with('association')
            ->will($this->returnValue(true));

        $this->setEntityMetadata(array($rootClass => $metadata));
        $this->setEntityConfigProvider($rootClass, 'association', true, false);

        $this->assertAttributeOptions(
            $this->guesser->guessAttributeParameters($rootClass, $propertyPath),
            null,
            'object',
            array('class' => 'Doctrine\Common\Collections\ArrayCollection')
        );
    }

    /**
     * @param TypeGuess|null $expected
     * @param Attribute $attribute
     * @param array $formMapping
     * @param array $formConfig
     * @dataProvider guessAttributeFormDataProvider
     */
    public function testGuessAttributeForm(
        $expected,
        Attribute $attribute,
        array $formMapping = array(),
        array $formConfig = array()
    ) {
        foreach ($formMapping as $mapping) {
            $this->guesser->addFormTypeMapping(
                $mapping['attributeType'],
                $mapping['formType'],
                $mapping['formOptions']
            );
        }

        if ($formConfig) {
            $this->formConfigProvider->expects($this->once())->method('hasConfig')
                ->with($formConfig['entity'])->will($this->returnValue(true));
            $formConfigObject = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
            $formConfigObject->expects($this->at(0))->method('has')->with('form_type')
                ->will($this->returnValue(true));
            $formConfigObject->expects($this->at(1))->method('get')->with('form_type')
                ->will($this->returnValue($formConfig['form_type']));
            $formConfigObject->expects($this->at(2))->method('has')->with('form_options')
                ->will($this->returnValue(true));
            $formConfigObject->expects($this->at(3))->method('get')->with('form_options')
                ->will($this->returnValue($formConfig['form_options']));
            $this->formConfigProvider->expects($this->once())->method('getConfig')
                ->with($formConfig['entity'])->will($this->returnValue($formConfigObject));
        }

        $this->assertEquals($expected, $this->guesser->guessAttributeForm($attribute));
    }

    public function guessAttributeFormDataProvider()
    {
        return array(
            'mapping guess' => array(
                'expected' => new TypeGuess('checkbox', array(), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('boolean'),
                'formMapping' => array(
                    array(
                        'attributeType' => 'boolean',
                        'formType' => 'checkbox',
                        'formOptions' => array()
                    )
                )
            ),
            'configured entity guess' => array(
                'expected' => new TypeGuess('test_type', array('key' => 'value'), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('entity', null, array('class' => 'TestEntity')),
                'formMapping' => array(),
                'formConfig' => array(
                    'entity' => 'TestEntity',
                    'form_type' => 'test_type',
                    'form_options' => array('key' => 'value')
                ),
            ),
            'regular entity guess' => array(
                'expected' => new TypeGuess(
                    'entity',
                    array('class' => 'TestEntity', 'multiple' => false),
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'attribute' => $this->createAttribute('entity', null, array('class' => 'TestEntity')),
            ),
            'no guess' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ),
        );
    }

    /**
     * @param string $expected
     * @param Attribute $attribute
     * @dataProvider guessClassAttributeFormDataProvider
     */
    public function testGuessClassAttributeForm($expected, Attribute $attribute)
    {
        $entityClass = 'TestEntity';
        $fieldName = 'field';

        $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())->method('hasAssociation')->with($fieldName)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())->method('getName')
            ->will($this->returnValue($entityClass));
        $this->setEntityMetadata(array($entityClass => $metadata));

        $typeGuesser = $this->getMockForAbstractClass('Symfony\Component\Form\FormTypeGuesserInterface');
        $typeGuesser->expects($this->any())->method('guessType')->with($entityClass, $fieldName)
            ->will($this->returnValue($expected));
        $this->formRegistry->expects($this->any())->method('getTypeGuesser')
            ->will($this->returnValue($typeGuesser));

        $this->assertEquals($expected, $this->guesser->guessClassAttributeForm($entityClass, $attribute));
    }

    public function guessClassAttributeFormDataProvider()
    {
        return array(
            'no property path' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array'),
            ),
            'no metadata and field' => array(
                'expected' => null,
                'attribute' => $this->createAttribute('array', 'field'),
            ),
            'guess' => array(
                'expected' => new TypeGuess('text', array(), TypeGuess::VERY_HIGH_CONFIDENCE),
                'attribute' => $this->createAttribute('string', 'entity.field'),
            )
        );
    }

    /**
     * @param string $type
     * @param string $propertyPath
     * @param array $options
     * @return Attribute
     */
    protected function createAttribute($type, $propertyPath = null, array $options = array())
    {
        $attribute = new Attribute();
        $attribute->setType($type)
            ->setPropertyPath($propertyPath)
            ->setOptions($options);

        return $attribute;
    }

    /**
     * @param array  $actualOptions
     * @param string|null $label
     * @param string $type
     * @param array $options
     */
    protected function assertAttributeOptions($actualOptions, $label, $type, array $options = array())
    {
        $this->assertNotNull($actualOptions);
        $this->assertInternalType('array', $actualOptions);
        $this->assertArrayHasKey('label', $actualOptions);
        $this->assertArrayHasKey('type', $actualOptions);
        $this->assertArrayHasKey('options', $actualOptions);
        $this->assertEquals($label, $actualOptions['label']);
        $this->assertEquals($type, $actualOptions['type']);
        $this->assertEquals($options, $actualOptions['options']);
    }

    /**
     * @param array $metadataArray
     */
    protected function setEntityMetadata(array $metadataArray)
    {
        $valueMap = array();
        foreach ($metadataArray as $entity => $metadata) {
            $valueMap[] = array($entity, $metadata);
        }

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
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
     * @param string $class
     * @param string $field
     * @param bool $multiple
     * @param bool $hasConfig
     * @param string|null $label
     */
    protected function setEntityConfigProvider($class, $field, $multiple = false, $hasConfig = true, $label = null)
    {
        $labelOption = $multiple ? 'plural_label' : 'label';

        $entityConfig = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $entityConfig->expects($this->any())->method('has')->with($labelOption)
            ->will($this->returnValue(!empty($label)));
        $entityConfig->expects($this->any())->method('get')->with($labelOption)
            ->will($this->returnValue($label));

        $this->entityConfigProvider->expects($this->any())->method('hasConfig')->with($class, $field)
            ->will($this->returnValue($hasConfig));
        $this->entityConfigProvider->expects($this->any())->method('getConfig')->with($class, $field)
            ->will($this->returnValue($entityConfig));
    }
}
