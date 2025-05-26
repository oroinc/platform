<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileMimeType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\ExternalFileUrl;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileFieldCompatibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraints\File as SymfonyFileConstraint;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileValidatorTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private FileConstraintsProvider&MockObject $fileConstraintsProvider;
    private ConfigFileValidator $configValidator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);

        $this->configValidator = new ConfigFileValidator($this->validator, $this->fileConstraintsProvider);
    }

    public function testValidateWhenFileIsNull(): void
    {
        self::assertEquals(
            new ConstraintViolationList(),
            $this->configValidator->validate(null, \stdClass::class, 'sampleField')
        );
    }

    public function testValidateWhenSymfonyFileAndNoFieldName(): void
    {
        $dataClass = \stdClass::class;
        $mimeTypes = ['sample/type1'];
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getAllowedMimeTypesForEntity')
            ->with($dataClass)
            ->willReturn($mimeTypes);

        $maxFileSize = 100;
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getMaxSizeForEntity')
            ->with($dataClass)
            ->willReturn($maxFileSize);

        $symfonyFile = $this->createMock(SymfonyFile::class);
        $constraintViolationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $symfonyFile,
                [
                    new Sequentially([
                        'constraints' => [
                            new SymfonyFileConstraint(
                                [
                                    'maxSize' => $maxFileSize,
                                    'mimeTypes' => $mimeTypes,
                                    'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
                                ]
                            ),
                        ],
                    ]),
                ]
            )
            ->willReturn($constraintViolationList);

        self::assertSame($constraintViolationList, $this->configValidator->validate($symfonyFile, $dataClass));
    }

    public function testValidateWhenExternalFileAndNoFieldName(): void
    {
        $dataClass = \stdClass::class;
        $mimeTypes = ['sample/type1'];
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getAllowedMimeTypesForEntity')
            ->with($dataClass)
            ->willReturn($mimeTypes);

        $regexp = '/sample-regexp/';
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getExternalFileAllowedUrlsRegExp')
            ->willReturn($regexp);

        $externalFile = new ExternalFile('');
        $constraintViolationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $externalFile,
                [
                    new Sequentially([
                        'constraints' => [
                            new ExternalFileUrl(
                                [
                                    'allowedUrlsRegExp' => $regexp,
                                    'emptyRegExpMessage' => 'oro.attachment.external_file.empty_regexp',
                                ]
                            ),
                            new ExternalFileMimeType(['mimeTypes' => $mimeTypes]),
                        ],
                    ]),
                ]
            )
            ->willReturn($constraintViolationList);

        self::assertSame($constraintViolationList, $this->configValidator->validate($externalFile, $dataClass));
    }

    public function testValidateWhenSymfonyFileAndHasFieldName(): void
    {
        $dataClass = \stdClass::class;
        $mimeTypes = ['sample/type1'];
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getAllowedMimeTypesForEntityField')
            ->with($dataClass, $fieldName = 'sampleField')
            ->willReturn($mimeTypes);

        $maxFileSize = 100;
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getMaxSizeForEntityField')
            ->with($dataClass, $fieldName)
            ->willReturn($maxFileSize);

        $symfonyFile = $this->createMock(SymfonyFile::class);
        $constraintViolationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $symfonyFile,
                [
                    new Sequentially([
                        'constraints' => [
                            new FileFieldCompatibility(['entityClass' => $dataClass, 'fieldName' => $fieldName]),
                            new SymfonyFileConstraint(
                                [
                                    'maxSize' => $maxFileSize,
                                    'mimeTypes' => $mimeTypes,
                                    'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
                                ]
                            ),
                        ],
                    ]),
                ]
            )
            ->willReturn($constraintViolationList);

        self::assertSame(
            $constraintViolationList,
            $this->configValidator->validate($symfonyFile, $dataClass, $fieldName)
        );
    }

    public function testValidateWhenExternalFileAndHasFieldName(): void
    {
        $dataClass = \stdClass::class;
        $mimeTypes = ['sample/type1'];
        $this->fileConstraintsProvider->expects(self::once())
            ->method('getAllowedMimeTypesForEntityField')
            ->with($dataClass, $fieldName = 'sampleField')
            ->willReturn($mimeTypes);

        $regexp = '/sample-regexp/';
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getExternalFileAllowedUrlsRegExp')
            ->willReturn($regexp);

        $externalFile = new ExternalFile('');
        $constraintViolationList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $externalFile,
                [
                    new Sequentially([
                        'constraints' => [
                            new FileFieldCompatibility(['entityClass' => $dataClass, 'fieldName' => $fieldName]),
                            new ExternalFileUrl(
                                [
                                    'allowedUrlsRegExp' => $regexp,
                                    'emptyRegExpMessage' => 'oro.attachment.external_file.empty_regexp',
                                ]
                            ),
                            new ExternalFileMimeType(['mimeTypes' => $mimeTypes]),
                        ],
                    ]),
                ]
            )
            ->willReturn($constraintViolationList);

        self::assertSame(
            $constraintViolationList,
            $this->configValidator->validate($externalFile, $dataClass, $fieldName)
        );
    }

    public function testValidateExternalFileUrl(): void
    {
        $url = 'http://example.org/image.png';
        $constraint = new ExternalFileUrl(
            [
                'allowedUrlsRegExp' => $this->fileConstraintsProvider->getExternalFileAllowedUrlsRegExp(),
                'emptyRegExpMessage' => 'oro.attachment.external_file.empty_regexp',
            ]
        );

        $constraintViolationList = new ConstraintViolationList();
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($url, $constraint)
            ->willReturn($constraintViolationList);

        self::assertSame($constraintViolationList, $this->configValidator->validateExternalFileUrl($url));
    }

    public function testValidateNotSupportedType(): void
    {
        $dataClass = \stdClass::class;

        $this->expectExceptionObject(
            new \InvalidArgumentException(
                sprintf('Argument of type "%s" is not supported', $dataClass)
            )
        );

        $this->validator->expects(self::never())
            ->method('validate');

        $this->configValidator->validate(new \stdClass(), $dataClass);
    }
}
