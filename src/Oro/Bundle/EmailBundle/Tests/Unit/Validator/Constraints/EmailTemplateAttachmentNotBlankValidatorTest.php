<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateAttachmentNotBlank;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateAttachmentNotBlankValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateAttachmentNotBlankValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): EmailTemplateAttachmentNotBlankValidator
    {
        return new EmailTemplateAttachmentNotBlankValidator();
    }

    public function testUnexpectedConstraintType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateAttachmentNotBlank"'
        );

        $value = new EmailTemplateAttachment();
        $constraint = $this->createMock(Constraint::class);

        $this->validator->validate($value, $constraint);
    }

    public function testUnexpectedValueType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment"'
        );

        $value = 'not an attachment';
        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);
    }

    public function testNoFieldsProvided(): void
    {
        $value = new EmailTemplateAttachment();
        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->setCode(EmailTemplateAttachmentNotBlank::ONLY_ONE_FIELD_NOT_BLANK)
            ->assertRaised();
    }

    public function testFileOnlyValid(): void
    {
        $value = new EmailTemplateAttachment();

        $file = $this->createMock(File::class);
        $uploadedFile = $this->createMock(UploadedFile::class);

        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $value->setFile($file);

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testFilePlaceholderOnlyValid(): void
    {
        $value = new EmailTemplateAttachment();
        $value->setFilePlaceholder('{{ entity.document }}');

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testFileAndFilePlaceholderInvalid(): void
    {
        $value = new EmailTemplateAttachment();

        $file = $this->createMock(File::class);
        $uploadedFile = $this->createMock(UploadedFile::class);

        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $value->setFile($file);
        $value->setFilePlaceholder('{{ entity.document }}');

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->setCode(EmailTemplateAttachmentNotBlank::ONLY_ONE_FIELD_NOT_BLANK)
            ->assertRaised();
    }

    public function testAllFieldsProvidedInvalid(): void
    {
        $value = new EmailTemplateAttachment();

        $file = $this->createMock(File::class);
        $uploadedFile = $this->createMock(UploadedFile::class);

        $file->expects(self::any())
            ->method('getFile')
            ->willReturn($uploadedFile);

        $value->setFile($file);
        $value->setFilePlaceholder('{{ entity.document }}');

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->setCode(EmailTemplateAttachmentNotBlank::ONLY_ONE_FIELD_NOT_BLANK)
            ->assertRaised();
    }

    public function testEmptyFileIsIgnored(): void
    {
        $value = new EmailTemplateAttachment();

        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getFile')
            ->willReturn(null);

        $value->setFile($file);
        $value->setFilePlaceholder('{{ entity.pdfFile }}');

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        // Since file is null, only filePlaceholder is provided, which is valid
        $this->assertNoViolation();
    }

    public function testEmptyFileAndNoOtherFieldsInvalid(): void
    {
        $value = new EmailTemplateAttachment();

        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getFile')
            ->willReturn(null);

        $value->setFile($file);

        $constraint = new EmailTemplateAttachmentNotBlank();

        $this->validator->validate($value, $constraint);

        // Since all fields are effectively empty, validation should fail
        $this->buildViolation($constraint->message)
            ->atPath('property.path')
            ->setCode(EmailTemplateAttachmentNotBlank::ONLY_ONE_FIELD_NOT_BLANK)
            ->assertRaised();
    }
}
