<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigFileValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileValidator */
    private $configValidator;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var FileConstraintsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileConstraintsProvider;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);

        $this->configValidator = new ConfigFileValidator($this->validator, $this->fileConstraintsProvider);
    }

    public function testValidateWhenNoFieldName(): void
    {
        $this->fileConstraintsProvider
            ->expects($this->once())
            ->method('getAllowedMimeTypesForEntity')
            ->with($dataClass = \stdClass::class)
            ->willReturn($mimeTypes = ['sample/type1']);

        $this->fileConstraintsProvider
            ->expects($this->once())
            ->method('getMaxSizeForEntity')
            ->with($dataClass = \stdClass::class)
            ->willReturn($maxFileSize = 100);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with(
                $componentFile = $this->createMock(ComponentFile::class),
                [new FileConstraint(['maxSize' => $maxFileSize, 'mimeTypes' => $mimeTypes])]
            )
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->assertInstanceOf(
            ConstraintViolationList::class,
            $this->configValidator->validate($componentFile, $dataClass)
        );
    }

    public function testValidateWhenFieldName(): void
    {
        $this->fileConstraintsProvider
            ->expects($this->once())
            ->method('getAllowedMimeTypesForEntityField')
            ->with($dataClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($mimeTypes = ['sample/type1']);

        $this->fileConstraintsProvider
            ->expects($this->once())
            ->method('getMaxSizeForEntityField')
            ->with($dataClass = \stdClass::class, $fieldName)
            ->willReturn($maxFileSize = 100);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with(
                $componentFile = $this->createMock(ComponentFile::class),
                [new FileConstraint(['maxSize' => $maxFileSize, 'mimeTypes' => $mimeTypes])]
            )
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->assertInstanceOf(
            ConstraintViolationList::class,
            $this->configValidator->validate($componentFile, $dataClass, $fieldName)
        );
    }
}
