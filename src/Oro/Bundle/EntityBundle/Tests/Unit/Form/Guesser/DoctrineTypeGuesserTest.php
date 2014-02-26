<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;

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
        $property = 'testProperty';

        $this->setEntityMetadata($class, null);

        $this->assertDefaultGuess($this->guesser->guessType($class, $property));
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

        $guess = $this->guesser->guessType($class, $firstField);
        $this->assertGuess($guess, $formType, array(), TypeGuess::VERY_HIGH_CONFIDENCE);

        $this->assertDefaultGuess($this->guesser->guessType($class, $secondField));
    }

    public function testGuessFieldSingleAssociation()
    {
        $class = 'Test\Entity';
        $property = 'testProperty';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($property)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->will($this->returnValue($associationClass));
        $metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->with($property)
            ->will($this->returnValue(false));
        $this->setEntityMetadata($class, $metadata);

        $this->assertGuess(
            $this->guesser->guessType($class, $property),
            'entity',
            array('class' => $associationClass, 'multiple' => false),
            TypeGuess::VERY_HIGH_CONFIDENCE
        );
    }

    public function testGuessFieldCollectionAssociation()
    {
        $class = 'Test\Entity';
        $property = 'testProperty';
        $associationClass = 'Test\Association\Class';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->with($property)
            ->will($this->returnValue(true));
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->will($this->returnValue($associationClass));
        $metadata->expects($this->any())
            ->method('isCollectionValuedAssociation')
            ->with($property)
            ->will($this->returnValue(true));
        $this->setEntityMetadata($class, $metadata);

        $this->assertGuess(
            $this->guesser->guessType($class, $property),
            'entity',
            array('class' => $associationClass, 'multiple' => true),
            TypeGuess::VERY_HIGH_CONFIDENCE
        );
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

    /**
     * @param TypeGuess $guess
     * @param string $type
     * @param array $options
     * @param $confidence
     */
    protected function assertGuess($guess, $type, array $options, $confidence)
    {
        $this->assertInstanceOf('Symfony\Component\Form\Guess\TypeGuess', $guess);
        $this->assertEquals($type, $guess->getType());
        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }

    /**
     * @param TypeGuess $guess
     */
    protected function assertDefaultGuess($guess)
    {
        $this->assertGuess($guess, 'text', array(), TypeGuess::LOW_CONFIDENCE);
    }
}
