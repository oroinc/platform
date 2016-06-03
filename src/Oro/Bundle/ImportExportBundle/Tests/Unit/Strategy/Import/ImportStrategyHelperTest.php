<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Import;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;

class ImportStrategyHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extendConfigProvider;

    /**
     * @var ImportStrategyHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurableDataConverter;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\ValidatorInterface')
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurableDataConverter = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new ImportStrategyHelper(
            $this->managerRegistry,
            $this->validator,
            $this->translator,
            $this->fieldHelper,
            $this->configurableDataConverter
        );

        $this->helper->setConfigProvider($this->extendConfigProvider);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Basic and imported entities must be instances of the same class
     */
    public function testImportEntityException()
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \DateTime();
        $excludedProperties = array();

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Can't find entity manager for stdClass
     */
    public function testImportEntityEntityManagerException()
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $excludedProperties = array();

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($basicEntity));

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testImportEntity()
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $importedEntity->fieldOne = 'one';
        $importedEntity->fieldTwo = 'two';
        $importedEntity->excludedField = 'excluded';
        $excludedProperties = ['excludedField'];

        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->will($this->returnValue(['fieldOne', 'excludedField']));
        $metadata->expects($this->once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['fieldTwo', 'fieldThree']));

        $this->fieldHelper->expects($this->any())
            ->method('getObjectValue')
            ->will($this->returnValueMap([
                [$importedEntity, 'fieldOne', $importedEntity->fieldOne],
                [$importedEntity, 'fieldTwo', $importedEntity->fieldTwo],
            ]));
        $this->fieldHelper->expects($this->exactly(3))
            ->method('setObjectValue')
            ->withConsecutive(
                [$basicEntity, 'fieldOne', $importedEntity->fieldOne],
                [$basicEntity, 'fieldTwo', $importedEntity->fieldTwo]
            );

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(get_class($basicEntity))
            ->will($this->returnValue($metadata));

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(get_class($basicEntity))
            ->will($this->returnValue($entityManager));

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testValidateEntityNoErrors()
    {
        $entity = new \stdClass();

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($entity);

        $this->assertNull($this->helper->validateEntity($entity));
    }

    /**
     * @param string|null $path
     * @param string $error
     * @param string $expectedMessage
     * @dataProvider validateDataProvider
     */
    public function testValidateEntity($path, $error, $expectedMessage)
    {
        $entity = new \stdClass();

        $violation = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationInterface')
            ->getMock();
        $violation->expects($this->once())
            ->method('getPropertyPath')
            ->will($this->returnValue($path));
        $violation->expects($this->once())
            ->method('getMessage')
            ->will($this->returnValue($error));
        $violations = array($violation);
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($entity)
            ->will($this->returnValue($violations));

        $this->assertEquals(array($expectedMessage), $this->helper->validateEntity($entity));
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'without property path' => [
                'path' => null,
                'error' => 'Error',
                'expectedMessage' => 'Error',
            ],
            'with property path' => [
                'path' => 'testPath',
                'error' => 'Error',
                'expectedMessage' => 'testPath: Error',
            ]
        ];
    }

    /**
     * @dataProvider prefixDataProvider
     * @param string|null $prefix
     */
    public function testAddValidationErrors($prefix)
    {
        $validationErrors = array('Error1', 'Error2');
        $expectedPrefix = $prefix;

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->getMock();
        if (null === $prefix) {
            $context->expects($this->once())
                ->method('getReadOffset')
                ->will($this->returnValue(10));
            $this->translator->expects($this->once())
                ->method('trans')
                ->with('oro.importexport.import.error %number%', array('%number%' => 10))
                ->will($this->returnValue('TranslatedError 10'));
            $expectedPrefix = 'TranslatedError 10';
        }

        $context->expects($this->exactly(2))
            ->method('addError')
            ->with($this->stringStartsWith($expectedPrefix . ' Error'));

        $this->helper->addValidationErrors($validationErrors, $context, $prefix);
    }

    public function prefixDataProvider()
    {
        return array(
            array(null),
            array('tst')
        );
    }
}
