<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

use Oro\Bundle\EntityBundle\Form\Guesser\FormConfigGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Config;

class FormConfigGuesserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormConfigGuesser
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formConfigProvider;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new FormConfigGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider
        );
    }

    protected function tearDown()
    {
        unset($this->managerRegistry);
        unset($this->entityConfigProvider);
        unset($this->formConfigProvider);
        unset($this->guesser);
    }

    public function testGuessRequired()
    {
        $guess = $this->guesser->guessRequired('Test/Entity', 'testProperty');
        $this->assertValueGuess($guess, false, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessMaxLength()
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');
        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessPattern()
    {
        $guess = $this->guesser->guessMaxLength('Test/Entity', 'testProperty');
        $this->assertValueGuess($guess, null, ValueGuess::LOW_CONFIDENCE);
    }

    public function testGuessNoEntityManager()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->managerRegistry->expects($this->any())->method('getManagerForClass')->with($class)
            ->will($this->returnValue(null));

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoMetadata()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $this->setEntityMetadata($class, null);

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoFormConfig()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(false));

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessNoFormType()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $property, array());

        $this->assertDefaultTypeGuess($this->guesser->guessType($class, $property));
    }

    public function testGuessOnlyFormType()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $property, array('form_type' => $formType));

        $guess = $this->guesser->guessType($class, $property);
        $this->assertTypeGuess($guess, $formType, array(), TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessOnlyFormTypeWithLabel()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';
        $label = 'Test Field';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $property, array('form_type' => $formType));
        $this->setEntityConfig($class, $property, array('label' => $label));

        $guess = $this->guesser->guessType($class, $property);
        $this->assertTypeGuess($guess, $formType, array('label' => $label), TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessFormTypeWithOptions()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $formType = 'test_form_type';
        $formOptions = array(
            'required' => false,
            'label' => 'Test Field'
        );

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $property, array('form_type' => $formType, 'form_options' => $formOptions));
        $this->setEntityConfig($class, $property, array('label' => 'Not used label'));

        $guess = $this->guesser->guessType($class, $property);
        $this->assertTypeGuess($guess, $formType, $formOptions, TypeGuess::HIGH_CONFIDENCE);
    }

    public function testGuessByAssociationClass()
    {
        $class = 'Test/Entity';
        $property = 'testProperty';
        $associationClass = 'Test/Association/Entity';
        $associationFormType = 'test_form_type';
        $associationFormOptions = array(
            'required' => false,
            'label' => 'Test Field'
        );

        $sourceClassMetadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $sourceClassMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($property)
            ->will($this->returnValue(true));
        $sourceClassMetadata->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with($property)
            ->will($this->returnValue(true));
        $sourceClassMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($property)
            ->will($this->returnValue($associationClass));
        $sourceEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $sourceEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($class)
            ->will($this->returnValue($sourceClassMetadata));

        $associationClassMetadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $associationEntityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $associationEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($associationClass)
            ->will($this->returnValue($associationClassMetadata));

        $this->managerRegistry->expects($this->at(0))->method('getManagerForClass')->with($class)
            ->will($this->returnValue($sourceEntityManager));
        $this->managerRegistry->expects($this->at(1))->method('getManagerForClass')->with($associationClass)
            ->will($this->returnValue($associationEntityManager));

        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $sourceEntityConfig */
        $sourceEntityConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $sourceEntityConfig->setValues(array());
        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $associationEntityConfig */
        $associationEntityConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $associationEntityConfig->setValues(
            array('form_type' => $associationFormType, 'form_options' => $associationFormOptions)
        );

        $this->formConfigProvider->expects($this->at(0))
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->at(1))
            ->method('getConfig')
            ->with($class, $property)
            ->will($this->returnValue($sourceEntityConfig));
        $this->formConfigProvider->expects($this->at(2))
            ->method('hasConfig')
            ->with($associationClass, null)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->at(3))
            ->method('getConfig')
            ->with($associationClass, null)
            ->will($this->returnValue($associationEntityConfig));

        $guess = $this->guesser->guessType($class, $property);
        $this->assertTypeGuess($guess, $associationFormType, $associationFormOptions, TypeGuess::HIGH_CONFIDENCE);
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
     * @param string $class
     * @param string $property
     * @param array $parameters
     */
    protected function setFormConfig($class, $property, array $parameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $config */
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $config->setValues($parameters);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->will($this->returnValue($config));
    }

    /**
     * @param string $class
     * @param string $property
     * @param array $parameters
     */
    protected function setEntityConfig($class, $property, array $parameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $config */
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $config->setValues($parameters);

        $this->entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(true));
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $property)
            ->will($this->returnValue($config));
    }

    /**
     * @param TypeGuess $guess
     * @param string $type
     * @param array $options
     * @param $confidence
     */
    protected function assertTypeGuess($guess, $type, array $options, $confidence)
    {
        $this->assertInstanceOf('Symfony\Component\Form\Guess\TypeGuess', $guess);
        $this->assertEquals($type, $guess->getType());
        $this->assertEquals($options, $guess->getOptions());
        $this->assertEquals($confidence, $guess->getConfidence());
    }

    /**
     * @param TypeGuess $guess
     */
    protected function assertDefaultTypeGuess($guess)
    {
        $this->assertTypeGuess($guess, 'text', array(), TypeGuess::LOW_CONFIDENCE);
    }

    /**
     * @param ValueGuess $guess
     * @param mixed $value
     * @param $confidence
     */
    protected function assertValueGuess($guess, $value, $confidence)
    {
        $this->assertInstanceOf('Symfony\Component\Form\Guess\ValueGuess', $guess);
        $this->assertEquals($value, $guess->getValue());
        $this->assertEquals($confidence, $guess->getConfidence());
    }
}
