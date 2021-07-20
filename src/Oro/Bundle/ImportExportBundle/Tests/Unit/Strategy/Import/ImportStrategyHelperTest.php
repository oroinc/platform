<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Import;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportStrategyHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject | ManagerRegistry */
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

    /** @var \PHPUnit\Framework\MockObject\MockObject | OwnerChecker */
    protected $ownerChecker;

    /** @var ImportStrategyHelper */
    protected $helper;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configurableDataConverter = $this->createMock(ConfigurableTableDataConverter::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->ownerChecker = $this->createMock(OwnerChecker::class);

        $this->helper = new ImportStrategyHelper(
            $this->managerRegistry,
            $this->validator,
            $this->translator,
            $this->fieldHelper,
            $this->configurableDataConverter,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->ownerChecker
        );

        $this->helper->setConfigProvider($this->extendConfigProvider);
    }

    public function testImportEntityException()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Basic and imported entities must be instances of the same class');

        $basicEntity = new \stdClass();
        $importedEntity = new \DateTime();
        $excludedProperties = array();

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testGetEntityManagerWithException()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\LogicException::class);
        $this->expectExceptionMessage("Can't find entity manager for stdClass");

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

        $context = $this->createMock(ContextInterface::class);
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

    public function testIsGrantedNoUser()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $object = new \stdClass();
        $this->assertTrue($this->helper->isGranted('EDIT', $object));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $isGranted
     */
    public function testIsGrantedObject($isGranted)
    {
        $attributes = 'EDIT';
        $object = new \stdClass();

        $this->assertIsGrantedCall($isGranted, $attributes, $object);

        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $isGranted
     */
    public function testIsGrantedObjectProperty($isGranted)
    {
        $attributes = 'EDIT';
        $object = new \stdClass();
        $property = 'test';

        $this->assertIsGrantedCall($isGranted, $attributes, $object, $property);

        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider isGrantedWhenUsingCacheDataProvider
     *
     * @param bool $isGranted
     * @param string|string[] $attributes
     * @param object|string $object
     */
    public function testIsGrantedWhenUsingCache(bool $isGranted, $attributes, $object)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $object)
            ->willReturn($isGranted);

        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object));

        // Ensures that we get same result while authorizationChecker::isGranted is not called again.
        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object));
    }

    public function isGrantedWhenUsingCacheDataProvider(): array
    {
        return [
            [
                'isGranted' => true,
                'attributes' => 'CREATE',
                'object' => new \stdClass(),
            ],
            [
                'isGranted' => false,
                'attributes' => 'CREATE',
                'object' => 'entity:\stdClass',
            ],
            [
                'isGranted' => true,
                'attributes' => ['CREATE', 'EDIT'],
                'object' => 'entity:\stdClass',
            ],
            [
                'isGranted' => false,
                'attributes' => ['CREATE', 'EDIT'],
                'object' => new \stdClass(),
            ],
            [
                'isGranted' => true,
                'attributes' => 'CREATE',
                'object' => new FieldVote(new \stdClass(), 'testField'),
            ],
            [
                'isGranted' => false,
                'attributes' => ['CREATE', 'EDIT'],
                'object' => new FieldVote(new \stdClass(), 'testField'),
            ],
        ];
    }

    /**
     * @dataProvider isGrantedForPropertyWhenUsingCacheDataProvider
     *
     * @param bool $isGranted
     * @param string|string[] $attributes
     * @param object|string $object
     * @param string|null $property
     */
    public function testIsGrantedForPropertyWhenUsingCache(bool $isGranted, $attributes, $object, $property)
    {
        $this->tokenAccessor->expects($this->any())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, new FieldVote($object, $property))
            ->willReturn($isGranted);

        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));

        // Ensures that we get same result while authorizationChecker::isGranted is not called again.
        $this->assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));
    }

    public function isGrantedForPropertyWhenUsingCacheDataProvider(): array
    {
        return [
            [
                'isGranted' => true,
                'attributes' => 'CREATE',
                'object' => new \stdClass(),
                'property' => 'testField',
            ],
            [
                'isGranted' => false,
                'attributes' => 'CREATE',
                'object' => 'entity:\stdClass',
                'property' => 'testField',
            ],
            [
                'isGranted' => true,
                'attributes' => ['CREATE', 'EDIT'],
                'object' => 'entity:\stdClass',
                'property' => 'testField',
            ],
            [
                'isGranted' => false,
                'attributes' => ['CREATE', 'EDIT'],
                'object' => new \stdClass(),
                'property' => 'testField',
            ],
        ];
    }

    public function testCheckPermissionGrantedForEntity()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(true, 'CREATE', $entity);

        $this->assertTrue($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckPermissionGrantedForEntityIsDenied()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(false, 'CREATE', $entity);

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_entity');

        $this->assertFalse($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckEntityOwnerPermissions()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(true);
        $this->translator->expects($this->never())
            ->method($this->anything());

        $this->assertTrue($this->helper->checkEntityOwnerPermissions($context, $entity));
    }

    public function testCheckEntityOwnerPermissionsDenied()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getReadOffset')
            ->willReturn(1);

        $entity = new \stdClass();

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(false);

        $this->assertAddValidationErrorCalled($context, 'oro.importexport.import.errors.wrong_owner');

        $this->assertFalse($this->helper->checkEntityOwnerPermissions($context, $entity));
    }

    public function testCheckEntityOwnerPermissionsDeniedWhenSuppressErrors(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getReadOffset')
            ->willReturn(1);

        $entity = new \stdClass();

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(false);

        $context->expects($this->never())
            ->method('addError');

        $this->assertFalse($this->helper->checkEntityOwnerPermissions($context, $entity, true));
    }

    public function testCheckImportedEntityFieldsAclGrantedForNewEntity()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects($this->never())
            ->method('addError');

        $this->assertIsGrantedCall(true, 'CREATE', new ObjectIdentity('entity', 'stdClass'), 'testField');

        $this->assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclGrantedForExistingEntity()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects($this->never())
            ->method('addError');

        $this->assertIsGrantedCall(true, 'EDIT', $existingEntity, 'testField');

        $this->assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclNotGrantedForExistingEntityNoValue()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects($this->never())
            ->method('addError');

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['anotherField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclAccessDeniedNewEntity()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);
        $this->fieldHelper->expects($this->once())
            ->method('setObjectValue')
            ->with($entity, 'testField', null);

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_property_entity');
        $this->assertIsGrantedCall(false, 'CREATE', new ObjectIdentity('entity', 'stdClass'), 'testField');

        $this->assertFalse(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclAccessDeniedExistingEntity()
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);
        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->with($existingEntity, 'testField')
            ->willReturn('OLD');
        $this->fieldHelper->expects($this->once())
            ->method('setObjectValue')
            ->with($entity, 'testField', 'OLD');

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_property_entity');
        $this->assertIsGrantedCall(false, 'EDIT', $existingEntity, 'testField');

        $this->assertFalse(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
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

    /**
     * @param bool $isGranted
     * @param mixed $attributes
     * @param mixed $object
     * @param string|null $property
     */
    protected function assertIsGrantedCall($isGranted, $attributes, $object, $property = null)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $aclObject = $object;
        if ($property) {
            $aclObject = new FieldVote($object, $property);
        }
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attributes, $aclObject)
            ->willReturn($isGranted);
    }

    /**
     * @param ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context
     * @param string $error
     */
    protected function assertAddValidationErrorCalled(ContextInterface $context, $error)
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                [$error],
                ['oro.importexport.import.error %number%']
            )
            ->willReturnCallback(static function ($msg) {
                return $msg . ' TR';
            });

        $context->expects($this->once())
            ->method('addError')
            ->with('oro.importexport.import.error %number% TR ' . $error . ' TR');
    }

    /**
     * @param ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context
     * @param string $msg
     */
    protected function assertAddError($context, $msg)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($msg)
            ->willReturnCallback(static function ($msg) {
                return $msg . ' TR';
            });

        $context->expects($this->once())
            ->method('addError')
            ->with($msg . ' TR');
    }

    public function testGetCurrentRowNumber(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->expects($this->once())
            ->method('getReadOffset')
            ->willReturn($rowNumber = 10);

        $this->assertEquals($rowNumber, $this->helper->getCurrentRowNumber($context));
    }

    public function testGetCurrentRowNumberWhenBatchContextInterface(): void
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context
            ->expects($this->once())
            ->method('getReadOffset')
            ->willReturn(10);

        $context
            ->expects($this->once())
            ->method('getBatchSize')
            ->willReturn(100);

        $context
            ->expects($this->once())
            ->method('getBatchNumber')
            ->willReturn(2);

        $this->assertEquals(110, $this->helper->getCurrentRowNumber($context));
    }
}
