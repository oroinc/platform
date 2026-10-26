<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileParentFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileParentFieldConfigValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class FileParentFieldConfigValidatorTest extends ConstraintValidatorTestCase
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
        return new FileParentFieldConfigValidator($this->configFileValidator, $this->validationTargetProvider);
    }

    public function testValidateWithUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, new NotBlank());
    }

    /**
     * @dataProvider notUploadedFileDataProvider
     */
    public function testValidateWhenValueIsNotUploadedFile(?\SplFileInfo $value): void
    {
        $this->setObject(new File());

        $this->validationTargetProvider->expects(self::never())
            ->method('getTargetForFile');
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate($value, new FileParentFieldConfig());

        $this->assertNoViolation();
    }

    public static function notUploadedFileDataProvider(): array
    {
        return [
            'no file' => ['value' => null],
            'external file' => ['value' => new ExternalFile('http://example.org/file.txt')],
        ];
    }

    public function testValidateWhenObjectIsNotFile(): void
    {
        $this->setObject(new \stdClass());

        $this->validationTargetProvider->expects(self::never())
            ->method('getTargetForFile');
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(new SymfonyFile('test.txt', false), new FileParentFieldConfig());

        $this->assertNoViolation();
    }

    public function testValidateWhenFileIsSubmittedWithAttachment(): void
    {
        $file = new File();
        $this->setObject($file);

        $this->validationTargetProvider->expects(self::once())
            ->method('isSubmittedWithAttachment')
            ->with(self::identicalTo($file))
            ->willReturn(true);
        $this->validationTargetProvider->expects(self::never())
            ->method('getTargetForFile');
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(new SymfonyFile('test.txt', false), new FileParentFieldConfig());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider targetDataProvider
     */
    public function testValidateByTargetConfig(FileValidationTarget $target): void
    {
        $uploadedFile = new SymfonyFile('test.txt', false);
        $file = new File();
        $this->setObject($file);

        $fieldConstraint = new NotBlank();
        $this->validationTargetProvider->expects(self::once())
            ->method('getTargetForFile')
            ->with(self::identicalTo($file))
            ->willReturn($target);
        $this->configFileValidator->expects(self::once())
            ->method('getConstraints')
            ->with(self::identicalTo($uploadedFile), self::identicalTo($target))
            ->willReturn([$fieldConstraint]);
        $this->expectValidateValue(0, $uploadedFile, [$fieldConstraint], ['Default']);

        $this->validator->validate($uploadedFile, new FileParentFieldConfig());

        $this->assertNoViolation();
    }

    public static function targetDataProvider(): array
    {
        return [
            'parent entity is not known' => ['target' => FileValidationTarget::system()],
            'parent entity field' => ['target' => new FileValidationTarget(\stdClass::class, 'avatar')],
        ];
    }
}
