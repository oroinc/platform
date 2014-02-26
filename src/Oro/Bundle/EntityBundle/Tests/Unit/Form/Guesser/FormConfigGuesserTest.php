<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Guesser;

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

        $this->entityConfigProvider
            = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->formConfigProvider
            = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();

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

    public function testGuessNoEntityManager()
    {
        $class = 'Test/Entity';

        $this->managerRegistry->expects($this->any())->method('getManagerForClass')->with($class)
            ->will($this->returnValue(null));

        $this->assertNull($this->guesser->guess($class));
    }

    public function testGuessNoMetadata()
    {
        $class = 'Test/Entity';

        $this->setEntityMetadata($class, null);

        $this->assertNull($this->guesser->guess($class));
    }

    public function testGuessNoFormConfig()
    {
        $class = 'Test/Entity';
        $field = 'testField';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $field)
            ->will($this->returnValue(false));

        $this->assertNull($this->guesser->guess($class, $field));
    }

    public function testGuessNoFormType()
    {
        $class = 'Test/Entity';
        $field = 'testField';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $field, array());

        $this->assertNull($this->guesser->guess($class, $field));
    }

    public function testGuessOnlyFormType()
    {
        $class = 'Test/Entity';
        $field = 'testField';
        $formType = 'test_form_type';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $field, array('form_type' => $formType));

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals($formType, $formBuildData->getFormType());
        $this->assertEquals(array(), $formBuildData->getFormOptions());
    }

    public function testGuessOnlyFormTypeWithLabel()
    {
        $class = 'Test/Entity';
        $field = 'testField';
        $formType = 'test_form_type';
        $label = 'Test Field';

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $field, array('form_type' => $formType));
        $this->setEntityConfig($class, $field, array('label' => $label));

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals($formType, $formBuildData->getFormType());
        $this->assertEquals(array('label' => $label), $formBuildData->getFormOptions());
    }

    public function testGuessFormTypeWithOptions()
    {
        $class = 'Test/Entity';
        $field = 'testField';
        $formType = 'test_form_type';
        $formOptions = array(
            'required' => false,
            'label' => 'Test Field'
        );

        $metadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $this->setEntityMetadata($class, $metadata);
        $this->setFormConfig($class, $field, array('form_type' => $formType, 'form_options' => $formOptions));
        $this->setEntityConfig($class, $field, array('label' => 'Not used label'));

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals($formType, $formBuildData->getFormType());
        $this->assertEquals($formOptions, $formBuildData->getFormOptions());
    }

    public function testGuessByAssociationClass()
    {
        $class = 'Test/Entity';
        $field = 'testField';
        $associationClass = 'Test/Association/Entity';
        $associationFormType = 'test_form_type';
        $associationFormOptions = array(
            'required' => false,
            'label' => 'Test Field'
        );

        $sourceClassMetadata = $this->getMockForAbstractClass('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $sourceClassMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $sourceClassMetadata->expects($this->once())
            ->method('isSingleValuedAssociation')
            ->with($field)
            ->will($this->returnValue(true));
        $sourceClassMetadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->with($field)
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
            ->with($class, $field)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->at(1))
            ->method('getConfig')
            ->with($class, $field)
            ->will($this->returnValue($sourceEntityConfig));
        $this->formConfigProvider->expects($this->at(2))
            ->method('hasConfig')
            ->with($associationClass, null)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->at(3))
            ->method('getConfig')
            ->with($associationClass, null)
            ->will($this->returnValue($associationEntityConfig));

        $formBuildData = $this->guesser->guess($class, $field);
        $this->assertInstanceOf('Oro\Bundle\FormBundle\Guesser\FormBuildData', $formBuildData);
        $this->assertEquals($associationFormType, $formBuildData->getFormType());
        $this->assertEquals($associationFormOptions, $formBuildData->getFormOptions());
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
     * @param string $field
     * @param array $parameters
     */
    protected function setFormConfig($class, $field, array $parameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $config */
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $config->setValues($parameters);

        $this->formConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $field)
            ->will($this->returnValue(true));
        $this->formConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $field)
            ->will($this->returnValue($config));
    }

    /**
     * @param string $class
     * @param string $field
     * @param array $parameters
     */
    protected function setEntityConfig($class, $field, array $parameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Config $config */
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $config->setValues($parameters);

        $this->entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->with($class, $field)
            ->will($this->returnValue(true));
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($class, $field)
            ->will($this->returnValue($config));
    }
}
