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

    /** @var \PHPUnit\Framework\MockObject\MockObject | ConfigProvider */
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

    public function testImportEntityException(): void
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Basic and imported entities must be instances of the same class');

        $basicEntity = new \stdClass();
        $importedEntity = new \DateTime();
        $excludedProperties = array();

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testGetEntityManagerWithException(): void
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\LogicException::class);
        $this->expectExceptionMessage("Can't find entity manager for stdClass");

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn(null);

        $this->helper->getEntityManager('stdClass');
    }

    public function testGetLoggedUser(): void
    {
        $loggedUser = new User();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($loggedUser);

        self::assertSame($loggedUser, $this->helper->getLoggedUser());
    }

    public function testImportNonConfigurableEntity(): void
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $importedEntity->fieldOne = 'one';
        $importedEntity->fieldTwo = 'two';
        $importedEntity->excludedField = 'excluded';
        $excludedProperties = ['excludedField'];

        $metadata = $this->createMock('\Doctrine\ORM\Mapping\ClassMetadata');
        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->will($this->returnValue(['fieldOne', 'excludedField']));

        $metadata->expects(self::once())
            ->method('getAssociationNames')
            ->will($this->returnValue(['fieldTwo', 'fieldThree']));

        $this->fieldHelper->expects(self::any())
            ->method('getObjectValue')
            ->will($this->returnValueMap([
                [$importedEntity, 'fieldOne', $importedEntity->fieldOne],
                [$importedEntity, 'fieldTwo', $importedEntity->fieldTwo],
            ]));

        $this->fieldHelper->expects(self::exactly(3))
            ->method('setObjectValue')
            ->withConsecutive(
                [$basicEntity, 'fieldOne', $importedEntity->fieldOne],
                [$basicEntity, 'fieldTwo', $importedEntity->fieldTwo]
            );

        $this->extendConfigProvider
            ->method('hasConfig')
            ->willReturn(false);

        $entityManager = $this->createMock('Doctrine\ORM\EntityManager');
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(get_class($basicEntity))
            ->will($this->returnValue($metadata));

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(get_class($basicEntity))
            ->will($this->returnValue($entityManager));

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testImportConfigurableEntity(): void
    {
        $basicEntity = new \stdClass();
        $importedEntity = new \stdClass();
        $importedEntity->fieldOne = 'one';
        $importedEntity->fieldTwo = 'two';
        $importedEntity->excludedField = 'excluded';
        $importedEntity->deletedField = 'deleted';
        $excludedProperties = ['excludedField'];

        $this->fieldHelper->expects(self::once())
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

        $this->fieldHelper->expects(self::any())
            ->method('getObjectValue')
            ->will($this->returnValueMap([
                [$importedEntity, 'fieldOne', $importedEntity->fieldOne],
                [$importedEntity, 'fieldTwo', $importedEntity->fieldTwo],
            ]));

        $this->fieldHelper->expects(self::exactly(3))
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

    public function testValidateEntityNoErrors(): void
    {
        $entity = new \stdClass();

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($entity)
            ->willReturn(new ConstraintViolationList());

        self::assertNull($this->helper->validateEntity($entity));
    }

    /**
     * @param string|null $path
     * @param string $error
     * @param string $expectedMessage
     * @dataProvider validateDataProvider
     */
    public function testValidateEntity(?string $path, string $error, string $expectedMessage): void
    {
        $entity = new \stdClass();

        $violation = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationInterface')
            ->getMock();
        $violation->expects(self::once())
            ->method('getPropertyPath')
            ->will($this->returnValue($path));
        $violation->expects(self::once())
            ->method('getMessage')
            ->will($this->returnValue($error));
        $violations = array($violation);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($entity)
            ->will($this->returnValue($violations));

        self::assertEquals(array($expectedMessage), $this->helper->validateEntity($entity));
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
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
    public function testAddValidationErrors(?string $prefix): void
    {
        $validationErrors = array('Error1', 'Error2');
        $expectedPrefix = $prefix;

        $context = $this->createMock(ContextInterface::class);
        if (null === $prefix) {
            $context->expects(self::once())
                ->method('getReadOffset')
                ->will($this->returnValue(10));
            $this->translator->expects(self::once())
                ->method('trans')
                ->with('oro.importexport.import.error %number%', array('%number%' => 10))
                ->will($this->returnValue('TranslatedError 10'));
            $expectedPrefix = 'TranslatedError 10';
        }

        $context->expects(self::exactly(2))
            ->method('addError')
            ->with($this->stringStartsWith($expectedPrefix . ' Error'));

        $this->helper->addValidationErrors($validationErrors, $context, $prefix);
    }

    /**
     * @return array
     */
    public function prefixDataProvider(): array
    {
        return [
            [null],
            ['tst'],
        ];
    }

    public function testIsGrantedNoUser(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(false);

        $object = new \stdClass();
        self::assertTrue($this->helper->isGranted('EDIT', $object));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $isGranted
     */
    public function testIsGrantedObject(bool $isGranted): void
    {
        $attributes = 'EDIT';
        $object = new \stdClass();

        $this->assertIsGrantedCall($isGranted, $attributes, $object);

        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $isGranted
     */
    public function testIsGrantedObjectProperty(bool $isGranted): void
    {
        $attributes = 'EDIT';
        $object = new \stdClass();
        $property = 'test';

        $this->assertIsGrantedCall($isGranted, $attributes, $object, $property);

        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider(): array
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
     * @param array $isGrantedCalls
     * @param string|string[] $attributes
     * @param object|string $object
     */
    public function testIsGrantedWhenUsingCache(bool $isGranted, array $isGrantedCalls, $attributes, $object): void
    {
        $this->tokenAccessor->expects(self::any())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects(self::exactly(count($isGrantedCalls)))
            ->method('isGranted')
            ->withConsecutive(...$isGrantedCalls)
            ->willReturn($isGranted);

        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object));

        // Ensures that we get same result while authorizationChecker::isGranted is not called again.
        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object));
    }

    /**
     * @return array
     */
    public function isGrantedWhenUsingCacheDataProvider(): array
    {
        $object = new \stdClass();
        $fieldObject = new FieldVote(new \stdClass(), 'testField');

        return [
            [
                'isGranted' => true,
                'isGrantedCalls' => [['CREATE', $object]],
                'attributes' => 'CREATE',
                'object' => $object,
            ],
            [
                'isGranted' => false,
                'isGrantedCalls' => [['CREATE', 'entity:\stdClass']],
                'attributes' => 'CREATE',
                'object' => 'entity:\stdClass',
            ],
            [
                'isGranted' => true,
                'isGrantedCalls' => [['CREATE', 'entity:\stdClass'], ['EDIT', 'entity:\stdClass']],
                'attributes' => ['CREATE', 'EDIT'],
                'object' => 'entity:\stdClass',
            ],
            [
                'isGranted' => false,
                'isGrantedCalls' => [['CREATE', $object]],
                'attributes' => ['CREATE', 'EDIT'],
                'object' => $object,
            ],
            [
                'isGranted' => true,
                'isGrantedCalls' => [['CREATE', $fieldObject]],
                'attributes' => 'CREATE',
                'object' => $fieldObject,
            ],
            [
                'isGranted' => false,
                'isGrantedCalls' => [['CREATE', $fieldObject]],
                'attributes' => ['CREATE', 'EDIT'],
                'object' => $fieldObject,
            ],
        ];
    }

    /**
     * @dataProvider isGrantedForPropertyWhenUsingCacheDataProvider
     *
     * @param bool $isGranted
     * @param array $isGrantedCalls
     * @param string|string[] $attributes
     * @param object|string $object
     * @param string|null $property
     */
    public function testIsGrantedForPropertyWhenUsingCache(
        bool $isGranted,
        array $isGrantedCalls,
        $attributes,
        $object,
        ?string $property
    ): void {
        $this->tokenAccessor->expects(self::any())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects(self::exactly(count($isGrantedCalls)))
            ->method('isGranted')
            ->withConsecutive(...$isGrantedCalls)
            ->willReturn($isGranted);

        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));

        // Ensures that we get same result while authorizationChecker::isGranted is not called again.
        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));
    }

    /**
     * @return array
     */
    public function isGrantedForPropertyWhenUsingCacheDataProvider(): array
    {
        $object = new \stdClass();
        $fieldEntityObject = new FieldVote($object, 'testField');
        $fieldEntityString = new FieldVote('entity:\stdClass', 'testField');

        return [
            [
                'isGranted' => true,
                'isGrantedCalls' => [['CREATE', $fieldEntityObject]],
                'attributes' => 'CREATE',
                'object' => $object,
                'property' => 'testField',
            ],
            [
                'isGranted' => false,
                'isGrantedCalls' => [['CREATE', $fieldEntityString]],
                'attributes' => 'CREATE',
                'object' => 'entity:\stdClass',
                'property' => 'testField',
            ],
            [
                'isGranted' => true,
                'isGrantedCalls' => [['CREATE', $fieldEntityString], ['EDIT', $fieldEntityString]],
                'attributes' => ['CREATE', 'EDIT'],
                'object' => 'entity:\stdClass',
                'property' => 'testField',
            ],
            [
                'isGranted' => false,
                'isGrantedCalls' => [['CREATE', $fieldEntityObject]],
                'attributes' => ['CREATE', 'EDIT'],
                'object' => new \stdClass(),
                'property' => 'testField',
            ],
        ];
    }

    public function testCheckPermissionGrantedForEntity(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(true, 'CREATE', $entity);

        self::assertTrue($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckPermissionGrantedForEntityIsDenied(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(false, 'CREATE', $entity);

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_entity');

        self::assertFalse($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckEntityOwnerPermissions(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();

        $this->ownerChecker->expects(self::once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(true);
        $this->translator->expects(self::never())
            ->method($this->anything());

        self::assertTrue($this->helper->checkEntityOwnerPermissions($context, $entity));
    }

    public function testCheckEntityOwnerPermissionsDenied(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getReadOffset')
            ->willReturn(1);

        $entity = new \stdClass();

        $this->ownerChecker->expects(self::once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(false);

        $this->assertAddValidationErrorCalled($context, 'oro.importexport.import.errors.wrong_owner');

        self::assertFalse($this->helper->checkEntityOwnerPermissions($context, $entity));
    }

    public function testCheckEntityOwnerPermissionsDeniedWhenSuppressErrors(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getReadOffset')
            ->willReturn(1);

        $entity = new \stdClass();

        $this->ownerChecker->expects(self::once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(false);

        $context->expects(self::never())
            ->method('addError');

        self::assertFalse($this->helper->checkEntityOwnerPermissions($context, $entity, true));
    }

    public function testCheckImportedEntityFieldsAclGrantedForNewEntity(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects(self::once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects(self::never())
            ->method('addError');

        $this->assertIsGrantedCall(true, 'CREATE', new ObjectIdentity('entity', 'stdClass'), 'testField');

        self::assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclGrantedForExistingEntity(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects(self::never())
            ->method('addError');

        $this->assertIsGrantedCall(true, 'EDIT', $existingEntity, 'testField');

        self::assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclNotGrantedForExistingEntityNoValue(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);

        $context->expects(self::never())
            ->method('addError');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertTrue(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['anotherField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclAccessDeniedNewEntity(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects(self::once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);
        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($entity, 'testField', null);

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_property_entity');
        $this->assertIsGrantedCall(false, 'CREATE', new ObjectIdentity('entity', 'stdClass'), 'testField');

        self::assertFalse(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    public function testCheckImportedEntityFieldsAclAccessDeniedExistingEntity(): void
    {
        /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getFields')
            ->willReturn([
                ['name' => 'testField']
            ]);
        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($existingEntity, 'testField')
            ->willReturn('OLD');
        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($entity, 'testField', 'OLD');

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_property_entity');
        $this->assertIsGrantedCall(false, 'EDIT', $existingEntity, 'testField');

        self::assertFalse(
            $this->helper->checkImportedEntityFieldsAcl($context, $entity, $existingEntity, ['testField' => 'TEST'])
        );
    }

    /**
     * @param array $fieldNames
     *
     * @return array
     */
    private function convertFieldNamesToFieldConfigs(array $fieldNames): array
    {
        return array_map(function ($fieldName) {
            return [ 'name' => $fieldName ];
        }, $fieldNames);
    }

    /**
     * @param bool $isGranted
     * @param string $attribute
     * @param mixed $object
     * @param string|null $property
     */
    protected function assertIsGrantedCall(bool $isGranted, string $attribute, $object, ?string $property = null): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('hasUser')
            ->willReturn(true);

        $aclObject = $object;
        if ($property) {
            $aclObject = new FieldVote($object, $property);
        }
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attribute, $aclObject)
            ->willReturn($isGranted);
    }

    /**
     * @param ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context
     * @param string $error
     */
    protected function assertAddValidationErrorCalled(ContextInterface $context, string $error): void
    {
        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->withConsecutive(
                [$error],
                ['oro.importexport.import.error %number%']
            )
            ->willReturnCallback(static function ($msg) {
                return $msg . ' TR';
            });

        $context->expects(self::once())
            ->method('addError')
            ->with('oro.importexport.import.error %number% TR ' . $error . ' TR');
    }

    /**
     * @param ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context
     * @param string $msg
     */
    protected function assertAddError($context, string $msg): void
    {
        $this->translator->expects(self::once())
            ->method('trans')
            ->with($msg)
            ->willReturnCallback(static function ($msg) {
                return $msg . ' TR';
            });

        $context->expects(self::once())
            ->method('addError')
            ->with($msg . ' TR');
    }

    public function testGetCurrentRowNumber(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->expects(self::once())
            ->method('getReadOffset')
            ->willReturn($rowNumber = 10);

        self::assertEquals($rowNumber, $this->helper->getCurrentRowNumber($context));
    }

    public function testGetCurrentRowNumberWhenBatchContextInterface(): void
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context
            ->expects(self::once())
            ->method('getReadOffset')
            ->willReturn(10);

        $context
            ->expects(self::once())
            ->method('getBatchSize')
            ->willReturn(100);

        $context
            ->expects(self::once())
            ->method('getBatchNumber')
            ->willReturn(2);

        self::assertEquals(110, $this->helper->getCurrentRowNumber($context));
    }
}
