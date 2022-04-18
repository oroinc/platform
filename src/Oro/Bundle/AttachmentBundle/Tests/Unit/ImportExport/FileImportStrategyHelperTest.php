<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileImportStrategyHelperTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'sampleField';
    private const ENTITY_CLASS = 'SampleClass';

    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private DatabaseHelper|\PHPUnit\Framework\MockObject\MockObject $databaseHelper;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject $configFileValidator;

    private ConfigMultipleFileValidator|\PHPUnit\Framework\MockObject\MockObject $configMultipleFileValidator;

    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private FileImportStrategyHelper $helper;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->databaseHelper = $this->createMock(DatabaseHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->configMultipleFileValidator = $this->createMock(ConfigMultipleFileValidator::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->helper = new FileImportStrategyHelper(
            $this->fieldHelper,
            $this->databaseHelper,
            $this->doctrineHelper,
            $this->configFileValidator,
            $this->configMultipleFileValidator,
            $this->translator
        );
    }

    public function testValidateSingleFileWhenNoFile(): void
    {
        $this->configFileValidator->expects(self::never())
            ->method(self::anything());

        $this->helper->validateSingleFile(new File(), new \stdClass(), 'sample_field');
    }

    public function testValidateSingleFileWhenNoViolations(): void
    {
        $file = new File();
        $symfonyFile = new SymfonyFile('/sample/path', false);
        $file->setFile($symfonyFile);
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $constraintViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->configFileValidator->expects(self::once())
            ->method('validate')
            ->with($symfonyFile, self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($constraintViolationList);

        self::assertCount(0, $this->helper->validateSingleFile($file, $entity, self::FIELD_NAME));
    }

    /**
     * @dataProvider validateSingleFileWhenViolationsDataProvider
     */
    public function testValidateSingleFileWhenViolations(?int $index, array $expectedResult): void
    {
        $file = new File();
        $symfonyFile = new SymfonyFile('/sample/path', false);
        $file->setFile($symfonyFile);
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $error1 = 'sample violation 1';
        $error2 = 'sample violation 2';
        $violation1 = $this->createViolation($error1);
        $violation2 = $this->createViolation($error2);
        $constraintViolationList = new ConstraintViolationList([$violation1, $violation2]);

        $this->configFileValidator->expects(self::once())
            ->method('validate')
            ->with($symfonyFile, self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($constraintViolationList);

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(function (string $key, array $params) {
                return sprintf('%s: %s', $key, json_encode($params));
            });

        $violations = $this->helper->validateSingleFile($file, $entity, self::FIELD_NAME, $index);
        self::assertEquals($expectedResult, $violations);
    }

    public function validateSingleFileWhenViolationsDataProvider(): array
    {
        return [
            [
                'index' => null,
                [
                    'oro.attachment.import.file_constraint_violation: {"%fieldname%":"sampleField","%path%":'
                    . '"\/sample\/path","%error%":"sample violation 1"}',
                    'oro.attachment.import.file_constraint_violation: {"%fieldname%":"sampleField","%path%":'
                    . '"\/sample\/path","%error%":"sample violation 2"}',
                ],
            ],
            [
                'index' => 2,
                [
                    'oro.attachment.import.multi_file_constraint_violation: {"%fieldname%":"sampleField","%path%":'
                    . '"\/sample\/path","%index%":3,"%error%":"sample violation 1"}',
                    'oro.attachment.import.multi_file_constraint_violation: {"%fieldname%":"sampleField","%path%":'
                    . '"\/sample\/path","%index%":3,"%error%":"sample violation 2"}',
                ],
            ],
        ];
    }

    private function createViolation(string $errorMessage): ConstraintViolation
    {
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects(self::any())
            ->method('getMessage')
            ->willReturn($errorMessage);

        return $violation;
    }

    public function testGetFromExistingEntityWhenNoEntity(): void
    {
        $entity = new \stdClass();

        $this->databaseHelper->expects(self::once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn(null);

        $this->fieldHelper->expects(self::never())
            ->method(self::anything());

        $this->helper->getFromExistingEntity($entity, self::FIELD_NAME);
    }

    public function testGetFromExistingEntityWhenDefault(): void
    {
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->databaseHelper->expects(self::once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn($existingEntity);

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($existingEntity, self::FIELD_NAME)
            ->willReturn(null);

        $default = 'sampleDefault';
        self::assertEquals($default, $this->helper->getFromExistingEntity($entity, self::FIELD_NAME, $default));
    }

    public function testGetFromExistingEntity(): void
    {
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->databaseHelper->expects(self::once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn($existingEntity);

        $value = 'sampleValue';

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($existingEntity, self::FIELD_NAME)
            ->willReturn($value);

        self::assertEquals($value, $this->helper->getFromExistingEntity($entity, self::FIELD_NAME));
    }

    public function testValidateFileCollectionWhenNoRelation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field invalidField not found in entity SampleClass');

        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $this->fieldHelper->expects(self::once())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn([]);

        $this->configMultipleFileValidator->expects(self::never())
            ->method(self::anything());

        $this->helper->validateFileCollection(new ArrayCollection(), $entity, 'invalidField');
    }

    public function testValidateFileCollectionWhenInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot validate unsupported field type invalidType of field sampleField in entity SampleClass'
        );

        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $fieldType = 'invalidType';
        $this->fieldHelper->expects(self::once())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn([self::FIELD_NAME => ['type' => $fieldType]]);

        $this->configMultipleFileValidator->expects(self::never())
            ->method(self::anything());

        $this->helper->validateFileCollection(new ArrayCollection(), $entity, self::FIELD_NAME);
    }

    /**
     * @dataProvider validateFileCollectionWhenNoViolationsDataProvider
     */
    public function testValidateFileCollectionWhenNoViolations(string $fieldType, string $methodName): void
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $this->fieldHelper->expects(self::any())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn([self::FIELD_NAME => ['type' => $fieldType, 'label' => self::FIELD_NAME . 'Label']]);

        $fileItems = new ArrayCollection([new FileItem(), new FileItem()]);

        $this->configMultipleFileValidator->expects(self::once())
            ->method($methodName)
            ->with($fileItems, self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(new ConstraintViolationList([]));

        self::assertEquals([], $this->helper->validateFileCollection($fileItems, $entity, self::FIELD_NAME));
    }

    public function validateFileCollectionWhenNoViolationsDataProvider(): array
    {
        return [
            [
                'fieldType' => 'multiFile',
                'methodName' => 'validateFiles',
            ],
            [
                'fieldType' => 'multiImage',
                'methodName' => 'validateImages',
            ],
        ];
    }

    /**
     * @dataProvider validateFileCollectionWhenViolationsDataProvider
     */
    public function testValidateFileCollectionWhenViolations(
        string $fieldType,
        string $methodName,
        array $violations,
        array $expectedViolations
    ): void {
        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $this->fieldHelper->expects(self::any())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn([self::FIELD_NAME => ['type' => $fieldType, 'label' => self::FIELD_NAME . 'Label']]);

        $fileItems = new ArrayCollection([new FileItem(), new FileItem()]);

        $this->configMultipleFileValidator->expects(self::once())
            ->method($methodName)
            ->with($fileItems, self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(new ConstraintViolationList($violations));

        $this->translator->expects(self::exactly(2))
            ->method('trans')
            ->willReturnCallback(function (string $key, array $params) {
                return sprintf('%s: %s', $key, json_encode($params));
            });

        self::assertEquals(
            $expectedViolations,
            $this->helper->validateFileCollection($fileItems, $entity, self::FIELD_NAME)
        );
    }

    public function validateFileCollectionWhenViolationsDataProvider(): array
    {
        return [
            [
                'fieldType' => 'multiFile',
                'methodName' => 'validateFiles',
                'violations' => [
                    $this->createViolation('sample file error 1'),
                    $this->createViolation('sample file error 2'),
                ],
                'expectedViolations' => [
                    'oro.attachment.import.multi_file_field_constraint_violation: {"%fieldname%":"sampleFieldLabel",'
                    . '"%error%":"sample file error 1"}',
                    'oro.attachment.import.multi_file_field_constraint_violation: {"%fieldname%":"sampleFieldLabel",'
                    . '"%error%":"sample file error 2"}',
                ],
            ],
            [
                'fieldType' => 'multiImage',
                'methodName' => 'validateImages',
                'violations' => [
                    $this->createViolation('sample image error 1'),
                    $this->createViolation('sample image error 2'),
                ],
                'expectedViolations' => [
                    'oro.attachment.import.multi_image_field_constraint_violation: {"%fieldname%":"sampleFieldLabel",'
                    . '"%error%":"sample image error 1"}',
                    'oro.attachment.import.multi_image_field_constraint_violation: {"%fieldname%":"sampleFieldLabel",'
                    . '"%error%":"sample image error 2"}',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldLabelDataProvider
     */
    public function testGetFieldLabel(array $relations, string $expectedLabel): void
    {
        $this->fieldHelper->expects(self::any())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn($relations);

        self::assertEquals($expectedLabel, $this->helper->getFieldLabel(self::ENTITY_CLASS, self::FIELD_NAME));
    }

    public function getFieldLabelDataProvider(): array
    {
        return [
            ['relations' => [], 'expectedLabel' => self::FIELD_NAME],
            ['relations' => [self::FIELD_NAME => ['type' => 'string']], 'expectedLabel' => self::FIELD_NAME],
            [
                'relations' => [self::FIELD_NAME => ['type' => 'string', 'label' => null]],
                'expectedLabel' => self::FIELD_NAME,
            ],
            [
                'relations' => [self::FIELD_NAME => ['type' => 'string', 'label' => 'Sample Label']],
                'expectedLabel' => 'Sample Label',
            ],
        ];
    }

    public function testValidateExternalFileUrl(): void
    {
        $url = 'http://example.org/image.png';
        $externalFile = new ExternalFile($url);
        $errorMessage = 'sample error';
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with($url)
            ->willReturn(new ConstraintViolationList([$this->createViolation($errorMessage)]));

        $label = 'Sample Label';
        $this->fieldHelper->expects(self::once())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS, false, true, true)
            ->willReturn([self::FIELD_NAME => ['type' => 'string', 'label' => $label]]);

        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $translatedErrorMessage = 'translated error message';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.attachment.import.file_external_url_violation',
                ['%error%' => $errorMessage, '%url%' => $url, '%fieldname%' => $label]
            )
            ->willReturn($translatedErrorMessage);

        self::assertEquals(
            [$translatedErrorMessage],
            $this->helper->validateExternalFileUrl($externalFile, $entity, self::FIELD_NAME)
        );
    }

    public function testValidateExternalFileUrlWhenNoViolations(): void
    {
        $url = 'http://example.org/image.png';
        $externalFile = new ExternalFile($url);
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with($url)
            ->willReturn(new ConstraintViolationList());

        $entity = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getClass')
            ->with($entity)
            ->willReturn(self::ENTITY_CLASS);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertEquals([], $this->helper->validateExternalFileUrl($externalFile, $entity, self::FIELD_NAME));
    }
}
