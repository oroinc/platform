<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Import;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportStrategyHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigurableTableDataConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableDataConverter;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var OwnerChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $ownerChecker;

    /** @var ImportStrategyHelper */
    private $helper;

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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Basic and imported entities must be instances of the same class');

        $basicEntity = new \stdClass();
        $importedEntity = new \DateTime();
        $excludedProperties = [];

        $this->helper->importEntity($basicEntity, $importedEntity, $excludedProperties);
    }

    public function testGetEntityManagerWithException(): void
    {
        $this->expectException(LogicException::class);
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

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['fieldOne', 'excludedField']);

        $metadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn(['fieldTwo', 'fieldThree']);

        $this->fieldHelper->expects(self::any())
            ->method('getObjectValue')
            ->willReturnMap([
                [$importedEntity, 'fieldOne', $importedEntity->fieldOne],
                [$importedEntity, 'fieldTwo', $importedEntity->fieldTwo],
            ]);

        $this->fieldHelper->expects(self::exactly(3))
            ->method('setObjectValue')
            ->withConsecutive(
                [$basicEntity, 'fieldOne', $importedEntity->fieldOne],
                [$basicEntity, 'fieldTwo', $importedEntity->fieldTwo]
            );

        $this->extendConfigProvider->expects(self::any())
            ->method('hasConfig')
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(get_class($basicEntity))
            ->willReturn($metadata);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(get_class($basicEntity))
            ->willReturn($entityManager);

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
            ->method('getEntityFields')
            ->with('stdClass', EntityFieldProvider::OPTION_WITH_RELATIONS)
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
            ->willReturnMap([
                [$importedEntity, 'fieldOne', $importedEntity->fieldOne],
                [$importedEntity, 'fieldTwo', $importedEntity->fieldTwo],
            ]);

        $this->fieldHelper->expects(self::exactly(3))
            ->method('setObjectValue')
            ->withConsecutive(
                [$basicEntity, 'fieldOne', $importedEntity->fieldOne],
                [$basicEntity, 'fieldTwo', $importedEntity->fieldTwo]
            );

        $this->extendConfigProvider->expects(self::any())
            ->method('hasConfig')
            ->willReturn(true);

        $this->extendConfigProvider->expects(self::any())
            ->method('getConfig')
            ->willReturnCallback(function ($className, $fieldName) {
                $configField = $this->createMock(Config::class);
                $configField->expects(self::any())
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
     * @dataProvider validateDataProvider
     */
    public function testValidateEntity(?string $path, string $error, string $expectedMessage): void
    {
        $entity = new \stdClass();

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn($path);
        $violation->expects(self::once())
            ->method('getMessage')
            ->willReturn($error);
        $violations = [$violation];
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($entity)
            ->willReturn($violations);

        self::assertEquals([$expectedMessage], $this->helper->validateEntity($entity));
    }

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
     */
    public function testAddValidationErrors(?string $prefix): void
    {
        $validationErrors = ['Error1', 'Error2'];
        $expectedPrefix = $prefix;

        $context = $this->createMock(ContextInterface::class);
        if (null === $prefix) {
            $context->expects(self::once())
                ->method('getReadOffset')
                ->willReturn(10);
            $this->translator->expects(self::once())
                ->method('trans')
                ->with('oro.importexport.import.error %number%', ['%number%' => 10])
                ->willReturn('TranslatedError 10');
            $expectedPrefix = 'TranslatedError 10';
        }

        $context->expects(self::exactly(2))
            ->method('addError')
            ->with(self::stringStartsWith($expectedPrefix . ' Error'));

        $this->helper->addValidationErrors($validationErrors, $context, $prefix);
    }

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
     */
    public function testIsGrantedObjectProperty(bool $isGranted): void
    {
        $attributes = 'EDIT';
        $object = new \stdClass();
        $property = 'test';

        $this->assertIsGrantedCall($isGranted, $attributes, $object, $property);

        self::assertEquals($isGranted, $this->helper->isGranted($attributes, $object, $property));
    }

    public function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider isGrantedWhenUsingCacheDataProvider
     */
    public function testIsGrantedWhenUsingCache(
        bool $isGranted,
        array $isGrantedCalls,
        array|string $attributes,
        object|string $object
    ): void {
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
     */
    public function testIsGrantedForPropertyWhenUsingCache(
        bool $isGranted,
        array $isGrantedCalls,
        array|string $attributes,
        object|string $object,
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(true, 'CREATE', $entity);

        self::assertTrue($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckPermissionGrantedForEntityIsDenied(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $this->assertIsGrantedCall(false, 'CREATE', $entity);

        $this->assertAddError($context, 'oro.importexport.import.errors.access_denied_entity');

        self::assertFalse($this->helper->checkPermissionGrantedForEntity($context, 'CREATE', $entity, 'stdClass'));
    }

    public function testCheckEntityOwnerPermissions(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();

        $this->ownerChecker->expects(self::once())
            ->method('isOwnerCanBeSet')
            ->with($entity)
            ->willReturn(true);
        $this->translator->expects(self::never())
            ->method(self::anything());

        self::assertTrue($this->helper->checkEntityOwnerPermissions($context, $entity));
    }

    public function testCheckEntityOwnerPermissionsDenied(): void
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::any())
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
        $context->expects(self::any())
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = null;

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
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
        $context = $this->createMock(ContextInterface::class);
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
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

    private function convertFieldNamesToFieldConfigs(array $fieldNames): array
    {
        return array_map(static fn ($fieldName) => ['name' => $fieldName], $fieldNames);
    }

    private function assertIsGrantedCall(
        bool $isGranted,
        string $attribute,
        mixed $object,
        ?string $property = null
    ): void {
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
    private function assertAddValidationErrorCalled(ContextInterface $context, string $error): void
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
    private function assertAddError(ContextInterface $context, string $msg): void
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
        $context->expects(self::once())
            ->method('getReadOffset')
            ->willReturn($rowNumber = 10);

        self::assertEquals($rowNumber, $this->helper->getCurrentRowNumber($context));
    }

    public function testGetCurrentRowNumberWhenBatchContextInterface(): void
    {
        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getReadOffset')
            ->willReturn(10);
        $context->expects(self::once())
            ->method('getBatchSize')
            ->willReturn(100);
        $context->expects(self::once())
            ->method('getBatchNumber')
            ->willReturn(2);

        self::assertEquals(110, $this->helper->getCurrentRowNumber($context));
    }
}
