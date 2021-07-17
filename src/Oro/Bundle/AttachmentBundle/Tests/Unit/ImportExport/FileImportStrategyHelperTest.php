<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper;
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
    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var DatabaseHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $databaseHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $configFileValidator;

    /** @var ConfigMultipleFileValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $configMultipleFileValidator;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FileImportStrategyHelper */
    private $helper;

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
        $this->configFileValidator
            ->expects($this->never())
            ->method($this->anything());

        $this->helper->validateSingleFile(new File(), new \stdClass(), 'sample_field');
    }

    public function testValidateSingleFileWhenNoViolations(): void
    {
        $file = new File();
        $symfonyFile = new SymfonyFile('/sample/path', false);
        $file->setFile($symfonyFile);
        $entity = new \stdClass();
        $fieldName = 'sampleField';

        $entityClass = 'SampleClass';
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $constraintViolationList = $this->createMock(ConstraintViolationListInterface::class);
        $constraintViolationList
            ->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->configFileValidator
            ->expects($this->once())
            ->method('validate')
            ->with($symfonyFile, $entityClass, $fieldName)
            ->willReturn($constraintViolationList);

        $this->assertCount(0, $this->helper->validateSingleFile($file, $entity, $fieldName));
    }

    /**
     * @dataProvider validateSingleFileWhenViolationsDataProvider
     *
     * @param int $index
     * @param array $expectedResult
     */
    public function testValidateSingleFileWhenViolations(?int $index, array $expectedResult): void
    {
        $file = new File();
        $symfonyFile = new SymfonyFile('/sample/path', false);
        $file->setFile($symfonyFile);
        $entity = new \stdClass();
        $fieldName = 'sampleField';

        $entityClass = 'SampleClass';
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $error1 = 'sample violation 1';
        $error2 = 'sample violation 2';
        $violation1 = $this->createViolation($error1);
        $violation2 = $this->createViolation($error2);
        $constraintViolationList = new ConstraintViolationList([$violation1, $violation2]);

        $this->configFileValidator
            ->expects($this->once())
            ->method('validate')
            ->with($symfonyFile, $entityClass, $fieldName)
            ->willReturn($constraintViolationList);

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                static function (string $key, array $params) {
                    return sprintf('%s: %s', $key, json_encode($params));
                }
            );

        $violations = $this->helper->validateSingleFile($file, $entity, $fieldName, $index);
        $this->assertEquals($expectedResult, $violations);
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
        $violation
            ->expects($this->any())
            ->method('getMessage')
            ->willReturn($errorMessage);

        return $violation;
    }

    public function testGetFromExistingEntityWhenNoEntity(): void
    {
        $entity = new \stdClass();

        $this->databaseHelper
            ->expects($this->once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn(null);

        $this->fieldHelper
            ->expects($this->never())
            ->method($this->anything());

        $this->helper->getFromExistingEntity($entity, 'sampleField');
    }

    public function testGetFromExistingEntityWhenDefault(): void
    {
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->databaseHelper
            ->expects($this->once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn($existingEntity);

        $fieldName = 'sampleField';

        $this->fieldHelper
            ->expects($this->once())
            ->method('getObjectValue')
            ->with($existingEntity, $fieldName)
            ->willReturn(null);

        $default = 'sampleDefault';
        $this->assertEquals($default, $this->helper->getFromExistingEntity($entity, $fieldName, $default));
    }

    public function testGetFromExistingEntity(): void
    {
        $entity = new \stdClass();
        $existingEntity = new \stdClass();

        $this->databaseHelper
            ->expects($this->once())
            ->method('findOneByIdentity')
            ->with($entity)
            ->willReturn($existingEntity);

        $fieldName = 'sampleField';
        $value = 'sampleValue';

        $this->fieldHelper
            ->expects($this->once())
            ->method('getObjectValue')
            ->with($existingEntity, $fieldName)
            ->willReturn($value);

        $this->assertEquals($value, $this->helper->getFromExistingEntity($entity, $fieldName));
    }

    public function testValidateFileCollectionWhenNoRelation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Field invalidField not found in entity SampleClass');

        $entity = new \stdClass();
        $fieldName = 'invalidField';
        $entityClass = 'SampleClass';

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->fieldHelper
            ->expects($this->once())
            ->method('getRelations')
            ->with($entityClass, false, true, true)
            ->willReturn([]);

        $this->configMultipleFileValidator
            ->expects($this->never())
            ->method($this->anything());

        $this->helper->validateFileCollection(new ArrayCollection(), $entity, $fieldName);
    }

    public function testValidateFileCollectionWhenInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Cannot validate unsupported field type invalidType of field sampleField in entity SampleClass'
        );

        $entity = new \stdClass();
        $fieldName = 'sampleField';
        $entityClass = 'SampleClass';

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $fieldType = 'invalidType';
        $this->fieldHelper
            ->expects($this->once())
            ->method('getRelations')
            ->with($entityClass, false, true, true)
            ->willReturn([$fieldName => ['type' => $fieldType]]);

        $this->configMultipleFileValidator
            ->expects($this->never())
            ->method($this->anything());

        $this->helper->validateFileCollection(new ArrayCollection(), $entity, $fieldName);
    }

    /**
     * @dataProvider validateFileCollectionWhenNoViolationsDataProvider
     */
    public function testValidateFileCollectionWhenNoViolations(string $fieldType, string $methodName): void
    {
        $entity = new \stdClass();
        $fieldName = 'sampleField';
        $entityClass = 'SampleClass';

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->fieldHelper
            ->expects($this->any())
            ->method('getRelations')
            ->with($entityClass, false, true, true)
            ->willReturn([$fieldName => ['type' => $fieldType, 'label' => $fieldName . 'Label']]);

        $fileItems = new ArrayCollection([new FileItem(), new FileItem()]);

        $this->configMultipleFileValidator
            ->expects($this->once())
            ->method($methodName)
            ->with($fileItems, $entityClass, $fieldName)
            ->willReturn(new ConstraintViolationList([]));

        $this->assertEquals([], $this->helper->validateFileCollection($fileItems, $entity, $fieldName));
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
        $fieldName = 'sampleField';
        $entityClass = 'SampleClass';

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->fieldHelper
            ->expects($this->any())
            ->method('getRelations')
            ->with($entityClass, false, true, true)
            ->willReturn([$fieldName => ['type' => $fieldType, 'label' => $fieldName . 'Label']]);

        $fileItems = new ArrayCollection([new FileItem(), new FileItem()]);

        $this->configMultipleFileValidator
            ->expects($this->once())
            ->method($methodName)
            ->with($fileItems, $entityClass, $fieldName)
            ->willReturn(new ConstraintViolationList($violations));

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                static function (string $key, array $params) {
                    return sprintf('%s: %s', $key, json_encode($params));
                }
            );

        $this->assertEquals(
            $expectedViolations,
            $this->helper->validateFileCollection($fileItems, $entity, $fieldName)
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
}
