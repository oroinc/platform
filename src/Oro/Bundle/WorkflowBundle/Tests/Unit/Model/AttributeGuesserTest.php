<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

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
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConfigProvider;

    protected function setUp()
    {
        $this->managerRegistry
            = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ManagerRegistry');

        $this->entityConfigProvider
            = $this->getMockForAbstractClass('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->guesser = new AttributeGuesser($this->managerRegistry, $this->entityConfigProvider);
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
