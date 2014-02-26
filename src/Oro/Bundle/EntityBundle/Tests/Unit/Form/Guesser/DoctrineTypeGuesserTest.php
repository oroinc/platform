<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\EntityBundle\Form\Guesser\DoctrineTypeGuesser;

class DoctrineTypeGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineTypeGuesser
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
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->entityConfigProvider
            = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->guesser = new DoctrineTypeGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider
        );
    }

    protected function tearDown()
    {
        unset($this->managerRegistry);
        unset($this->entityConfigProvider);
        unset($this->guesser);
    }

    public function testAddDoctrineTypeMapping()
    {
        $doctrineType = 'doctrine_type';
        $formType = 'test_form_type';
        $formOptions = array('form' => 'options');
        $expectedMappings = array($doctrineType => array('type' => $formType, 'options' => $formOptions));

        $this->guesser->addDoctrineTypeMapping($doctrineType, $formType, $formOptions);

        $this->assertAttributeEquals($expectedMappings, 'doctrineTypeMappings', $this->guesser);
    }

    public function testGuessNoMetadata()
    {
        $class = 'Test\Entity';

        $this->setEntityMetadata($class, null);

        $this->assertNull($this->guesser->guess($class));
    }

    public function testGuessNoField()
    {
        $class = 'Test\Entity';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);

        $formBuildData = $this->guesser->guess($class);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals('entity', $formBuildData->getFormType());
        $this->assertEquals(array('class' => $class, 'multiple' => false), $formBuildData->getFormOptions());
    }

    public function testGuessFieldWithoutAssociation()
    {
        $class = 'Test\Entity';
        $firstField = 'firstField';
        $secondField = 'secondField';

        $doctrineType = 'string';
        $formType = 'text';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($this->isType('string'))
            ->will($this->returnValue(false));
        $metadata->expects($this->any())
            ->method('getTypeOfField')
            ->with($this->isType('string'))
            ->will($this->returnValueMap(array(array($firstField, $doctrineType), array($secondField, 'object'))));
        $this->setEntityMetadata($class, $metadata);

        $this->guesser->addDoctrineTypeMapping($doctrineType, $formType);

        $formBuildData = $this->guesser->guess($class, $firstField);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals($formType, $formBuildData->getFormType());
        $this->assertEquals(array(), $formBuildData->getFormOptions());

        $formBuildData = $this->guesser->guess($class, $secondField);
        $this->assertNull($formBuildData);
    }

    public function testGuessFieldSingleAssociation()
    {
        $class = 'Test\Entity';
        $field = 'testField';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($field)
            ->will($this->returnValue($associationClass));
        $metadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $this->setEntityMetadata($class, $metadata);

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals('entity', $formBuildData->getFormType());
        $this->assertEquals(array('class' => $associationClass, 'multiple' => false), $formBuildData->getFormOptions());
    }

    public function testGuessFieldCollectionAssociation()
    {
        $class = 'Test\Entity';
        $field = 'testField';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($field)
            ->will($this->returnValue($associationClass));
        $metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $this->setEntityMetadata($class, $metadata);

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals('entity', $formBuildData->getFormType());
        $this->assertEquals(array('class' => $associationClass, 'multiple' => true), $formBuildData->getFormOptions());
    }

    public function testGuessFieldUnknownAssociation()
    {
        $class = 'Test\Entity';
        $field = 'testField';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($field)
            ->will($this->returnValue($associationClass));
        $this->setEntityMetadata($class, $metadata);

        $this->assertNull($this->guesser->guess($class, $field));
    }

    /**
     * @param string $class
     * @param mixed $metadata
     */
    protected function setEntityMetadata($class, $metadata)
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->will($this->returnValue($metadata));

        $this->managerRegistry->expects($this->any())->method('getManagerForClass')->with($class)
            ->will($this->returnValue($entityManager));
    }
}
