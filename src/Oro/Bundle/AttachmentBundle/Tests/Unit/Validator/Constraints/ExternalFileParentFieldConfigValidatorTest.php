<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileParentFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileParentFieldConfigValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ExternalFileParentFieldConfigValidatorTest extends ConstraintValidatorTestCase
{
    private ConfigFileValidator&MockObject $configFileValidator;
    private FileValidationTargetProvider&MockObject $validationTargetProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->validationTargetProvider = $this->createMock(FileValidationTargetProvider::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ConstraintValidator
    {
        return new ExternalFileParentFieldConfigValidator($this->configFileValidator, $this->validationTargetProvider);
    }

    public function testValidateWithUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, new NotBlank());
    }

    public function testValidateWhenObjectIsNotFile(): void
    {
        $this->setObject(new \stdClass());

        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(null, new ExternalFileParentFieldConfig());

        $this->assertNoViolation();
    }

    public function testValidateWhenFileHasNoFile(): void
    {
        $this->setObject(new File());

        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(null, new ExternalFileParentFieldConfig());

        $this->assertNoViolation();
    }

    public function testValidateWhenFileIsUploadedFile(): void
    {
        $file = new File();
        $file->setFile(new SymfonyFile('test.txt', false));
        $this->setObject($file);

        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(null, new ExternalFileParentFieldConfig());

        $this->assertNoViolation();
    }

    public function testValidateWhenFileIsSubmittedWithAttachment(): void
    {
        $file = new File();
        $file->setFile(new ExternalFile('http://example.org/file.txt'));
        $this->setObject($file);

        $this->validationTargetProvider->expects(self::once())
            ->method('isSubmittedWithAttachment')
            ->with(self::identicalTo($file))
            ->willReturn(true);
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(null, new ExternalFileParentFieldConfig());

        $this->assertNoViolation();
    }

    public function testValidateByTargetConfig(): void
    {
        $externalFile = new ExternalFile('http://example.org/file.txt');
        $file = new File();
        $file->setFile($externalFile);
        $this->setObject($file);

        $target = new FileValidationTarget(\stdClass::class, 'avatar');
        $fieldConstraint = new NotBlank();
        $this->validationTargetProvider->expects(self::once())
            ->method('getTargetForFile')
            ->with(self::identicalTo($file))
            ->willReturn($target);
        $this->configFileValidator->expects(self::once())
            ->method('getConstraints')
            ->with(self::identicalTo($externalFile), self::identicalTo($target))
            ->willReturn([$fieldConstraint]);
        $this->expectValidateValue(0, $externalFile, [$fieldConstraint], ['Default']);

        $this->validator->validate(null, new ExternalFileParentFieldConfig());

        $this->assertNoViolation();
    }
}
