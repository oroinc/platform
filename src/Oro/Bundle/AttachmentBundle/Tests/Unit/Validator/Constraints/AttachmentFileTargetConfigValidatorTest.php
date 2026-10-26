<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\AttachmentFileTargetConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\AttachmentFileTargetConfigValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class AttachmentFileTargetConfigValidatorTest extends ConstraintValidatorTestCase
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
        return new AttachmentFileTargetConfigValidator($this->configFileValidator, $this->validationTargetProvider);
    }

    public function testValidateWithUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Attachment(), new NotBlank());
    }

    public function testValidateWhenValueIsNotAttachment(): void
    {
        $this->validationTargetProvider->expects(self::never())
            ->method('getTargetForAttachment');
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate(new \stdClass(), new AttachmentFileTargetConfig());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider attachmentFileWithoutSupportedContentDataProvider
     */
    public function testValidateWhenAttachmentFileHasNoSupportedContent(?File $file): void
    {
        $attachment = new Attachment();
        $attachment->setFile($file);

        $this->validationTargetProvider->expects(self::never())
            ->method('getTargetForAttachment');
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate($attachment, new AttachmentFileTargetConfig());

        $this->assertNoViolation();
    }

    public static function attachmentFileWithoutSupportedContentDataProvider(): array
    {
        $fileWithPlainFileInfo = new File();
        $fileWithPlainFileInfo->setFile(new \SplFileInfo('test.txt'));

        return [
            'no file' => ['file' => null],
            'file without content' => ['file' => new File()],
            'file with not supported file info' => ['file' => $fileWithPlainFileInfo],
        ];
    }

    public function testValidateWhenAttachmentHasNoTarget(): void
    {
        $file = new File();
        $file->setFile(new SymfonyFile('test.txt', false));
        $attachment = new Attachment();
        $attachment->setFile($file);

        $this->validationTargetProvider->expects(self::once())
            ->method('getTargetForAttachment')
            ->with(self::identicalTo($attachment))
            ->willReturn(null);
        $this->configFileValidator->expects(self::never())
            ->method('getConstraints');

        $this->validator->validate($attachment, new AttachmentFileTargetConfig());

        $this->assertNoViolation();
    }

    public function testValidateByTargetEntityConfig(): void
    {
        $uploadedFile = new SymfonyFile('test.txt', false);
        $file = new File();
        $file->setFile($uploadedFile);
        $attachment = new Attachment();
        $attachment->setFile($file);

        $target = new FileValidationTarget(TestClass::class);
        $targetConstraint = new NotBlank();
        $this->validationTargetProvider->expects(self::once())
            ->method('getTargetForAttachment')
            ->with(self::identicalTo($attachment))
            ->willReturn($target);
        $this->configFileValidator->expects(self::once())
            ->method('getConstraints')
            ->with(self::identicalTo($uploadedFile), self::identicalTo($target))
            ->willReturn([$targetConstraint]);
        $this->expectValidateValueAt(0, 'file', $uploadedFile, [$targetConstraint], ['Default']);

        $this->validator->validate($attachment, new AttachmentFileTargetConfig());

        $this->assertNoViolation();
    }

    public function testValidateExternalFileByTargetEntityConfig(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png');
        $file = new File();
        $file->setFile($externalFile);
        $attachment = new Attachment();
        $attachment->setFile($file);

        $target = new FileValidationTarget(TestClass::class);
        $targetConstraint = new NotBlank();
        $this->validationTargetProvider->expects(self::once())
            ->method('getTargetForAttachment')
            ->with(self::identicalTo($attachment))
            ->willReturn($target);
        $this->configFileValidator->expects(self::once())
            ->method('getConstraints')
            ->with(self::identicalTo($externalFile), self::identicalTo($target))
            ->willReturn([$targetConstraint]);
        $this->expectValidateValueAt(0, 'file', $externalFile, [$targetConstraint], ['Default']);

        $this->validator->validate($attachment, new AttachmentFileTargetConfig());

        $this->assertNoViolation();
    }
}
