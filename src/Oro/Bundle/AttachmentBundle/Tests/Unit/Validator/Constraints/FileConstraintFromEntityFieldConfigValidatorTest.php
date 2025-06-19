<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromEntityFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromEntityFieldConfigValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FileConstraintFromEntityFieldConfigValidatorTest extends TestCase
{
    private const int MAX_SIZE = 1024;
    private const array MIME_TYPES = ['mime/type1'];

    private FileConstraintsProvider&MockObject $fileConstraintsProvider;
    private FileValidator&MockObject $fileValidator;
    private FileConstraintFromEntityFieldConfigValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileValidator = $this->createMock(FileValidator::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);

        $this->validator = new FileConstraintFromEntityFieldConfigValidator(
            $this->fileValidator,
            $this->fileConstraintsProvider
        );
    }

    public function testInitialize(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->fileValidator->expects($this->once())
            ->method('initialize')
            ->with($context);

        $this->validator->initialize($context);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected instance of ' . FileConstraintFromEntityFieldConfig::class . ', got ' . get_class($constraint)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidate(): void
    {
        $entityClass = 'SampleClass';
        $fieldName = 'sampleField';

        $constraint = $this->createMock(FileConstraintFromEntityFieldConfig::class);
        $constraint->expects($this->any())
            ->method('getEntityClass')
            ->willReturn($entityClass);

        $constraint->expects($this->any())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $this->fileConstraintsProvider->expects($this->once())
            ->method('getAllowedMimeTypesForEntityField')
            ->with($entityClass, $fieldName)
            ->willReturn(self::MIME_TYPES);

        $this->fileConstraintsProvider->expects($this->any())
            ->method('getMaxSizeForEntityField')
            ->with($entityClass, $fieldName)
            ->willReturn(self::MAX_SIZE);

        $this->fileValidator->expects($this->once())
            ->method('validate')
            ->with(
                $file = new \stdClass(),
                new File(
                    [
                        'mimeTypes' => self::MIME_TYPES,
                        'maxSize' => self::MAX_SIZE,
                        'mimeTypesMessage' => 'oro.attachment.mimetypes.invalid_mime_type',
                    ]
                )
            );

        $this->validator->validate($file, $constraint);
    }
}
