<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileFieldCompatibility;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileFieldCompatibilityValidator;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FileFieldCompatibilityValidatorTest extends ConstraintValidatorTestCase
{
    private const ENTITY_CLASS = \stdClass::class;
    private const FIELD_NAME = 'sampleField';
    private const SAMPLE_FIELD_LABEL = 'Sample Field Label';

    private AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $attachmentEntityConfigProvider;

    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    protected function setUp(): void
    {
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->fieldHelper
            ->expects(self::any())
            ->method('getRelations')
            ->with(self::ENTITY_CLASS)
            ->willReturn([self::FIELD_NAME => ['label' => self::SAMPLE_FIELD_LABEL]]);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidator
    {
        return new FileFieldCompatibilityValidator($this->attachmentEntityConfigProvider, $this->fieldHelper);
    }

    public function testNoViolationWhenValueIsNull(): void
    {
        $this->validator->validate(
            null,
            new FileFieldCompatibility(
                ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
            )
        );
        $this->assertNoViolation();
    }

    public function testNotConfigurableEntityForRegularField(): void
    {
        $value = new SymfonyFile('sample_file.txt', false);
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(null);

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testNotConfigurableEntityForExternallyStoredField(): void
    {
        $value = new ExternalFile('http://example.org/image.png');
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(null);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->incompatibleForExternalFileMessage)
            ->setParameter('{{ filename }}', '"' . $value->getFilename() . '"')
            ->setParameter('{{ field }}', self::SAMPLE_FIELD_LABEL)
            ->setCode(FileFieldCompatibility::INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR)
            ->assertRaised();
    }

    public function testExceptionWhenUnsupportedFile(): void
    {
        $value = new \SplFileInfo('sample_file.txt');
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($this->createMock(ConfigInterface::class));

        $this->expectExceptionObject(
            new UnexpectedValueException($value, sprintf('%s|%s', SymfonyFile::class, ExternalFile::class))
        );

        $this->validator->validate($value, $constraint);
    }

    public function testViolationWhenSymfonyFileForExternallyStoredField(): void
    {
        $value = new SymfonyFile('sample_file.txt', false);
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $fieldConfig = new Config($this->createMock(ConfigIdInterface::class), ['is_stored_externally' => true]);
        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($fieldConfig);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->incompatibleForRegularFileMessage)
            ->setParameter('{{ filename }}', '"' . $value->getFilename() . '"')
            ->setParameter('{{ field }}', self::SAMPLE_FIELD_LABEL)
            ->setCode(FileFieldCompatibility::INCOMPATIBLE_FIELD_FOR_REGULAR_FILE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenExternalFileForRegularField(): void
    {
        $value = new ExternalFile('http://example.org/image.png');
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $fieldConfig = new Config($this->createMock(ConfigIdInterface::class), ['is_stored_externally' => false]);
        $this->attachmentEntityConfigProvider
            ->expects(self::any())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($fieldConfig);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->incompatibleForExternalFileMessage)
            ->setParameter('{{ filename }}', '"' . $value->getFilename() . '"')
            ->setParameter('{{ field }}', self::SAMPLE_FIELD_LABEL)
            ->setCode(FileFieldCompatibility::INCOMPATIBLE_FIELD_FOR_EXTERNAL_FILE_ERROR)
            ->assertRaised();
    }

    public function testNoViolationWhenSymfonyFileForRegularField(): void
    {
        $value = new SymfonyFile('sample_file.txt', false);
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $fieldConfig = new Config($this->createMock(ConfigIdInterface::class), ['is_stored_externally' => false]);
        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($fieldConfig);

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testNoViolationWhenExternalFileForExternallyStoredField(): void
    {
        $value = new ExternalFile('http://example.org/image.png');
        $constraint = new FileFieldCompatibility(
            ['entityClass' => self::ENTITY_CLASS, 'fieldName' => self::FIELD_NAME]
        );

        $fieldConfig = new Config($this->createMock(ConfigIdInterface::class), ['is_stored_externally' => true]);
        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($fieldConfig);

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
