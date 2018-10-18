<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Import;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportStrategyHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject | ValidatorInterface */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject | TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject | FieldHelper */
    protected $fieldHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject | ConfigProvider  */
    protected $extendConfigProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject | ConfigurableTableDataConverter */
    protected $configurableDataConverter;

    /** @var \PHPUnit\Framework\MockObject\MockObject | AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject | TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ImportStrategyHelper */
    protected $helper;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configurableDataConverter = $this->createMock(ConfigurableTableDataConverter::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->helper = new ImportStrategyHelper(
            $this->managerRegistry,
            $this->validator,
            $this->translator,
            $this->fieldHelper,
            $this->configurableDataConverter,
            $this->authorizationChecker,
            $this->tokenAccessor
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
    public function testGetEntityManagerWithException()
    {
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn(null);

        $this->helper->getEntityManager('stdClass');
    }

    public function testGetLoggedUser()
    {
        $loggedUser = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->assertSame($loggedUser, $this->helper->getLoggedUser());
    }

    public function testImportNonConfigurableEntity()
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $importedEntity->fieldOne = 'one';
        $importedEntity->fieldTwo = 'two';
        $importedEntity->excludedField = 'excluded';
        $excludedProperties = ['excludedField'];

        $metadata = $this->createMock('\Doctrine\ORM\Mapping\ClassMetadata');
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

        $this->extendConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $entityManager = $this->createMock('Doctrine\ORM\EntityManager');
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

    public function testImportConfigurableEntity()
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $importedEntity->fieldOne = 'one';
        $importedEntity->fieldTwo = 'two';
        $importedEntity->excludedField = 'excluded';
        $importedEntity->deletedField = 'deleted';
        $excludedProperties = ['excludedField'];

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->with('stdClass', true)
            ->willReturn(
                $this->convertFieldNamesToFieldConfigs(
                    [
                        'fieldOne',
                        'fieldTwo',
                        'fieldThree',
                        'excludedField',
                        'deletedField',
                    ]
                )
            );

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

        $this->extendConfigProvider
            ->method('hasConfig')
            ->willReturn(true);

        $this->extendConfigProvider
            ->method('getConfig')
            ->willReturnCallback(function ($className, $fieldName) use ($importedEntity) {
                $configField = $this->createMock(Config::class);
                $configField
                    ->method('is')
                    ->with('is_deleted')
                    ->willReturn($fieldName === 'deletedField');

                return $configField;
            });

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testValidateEntityNoErrors()
    {
        $entity = new \stdClass();

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($entity)
            ->willReturn(new ConstraintViolationList());

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

    /**
     * @param array $fieldNames
     *
     * @return array
     */
    private function convertFieldNamesToFieldConfigs(array $fieldNames)
    {
        return array_map(function ($fieldName) {
            return [ 'name' => $fieldName ];
        }, $fieldNames);
    }
}
