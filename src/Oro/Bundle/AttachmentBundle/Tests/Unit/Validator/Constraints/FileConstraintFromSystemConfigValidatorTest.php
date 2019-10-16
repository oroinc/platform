<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfigValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FileConstraintFromSystemConfigValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_SIZE = 1024;
    private const MAX_SIZE2 = 2048;
    private const MIME_TYPES = ['mime/type1'];
    private const MIME_TYPES2 = ['mime/type2'];

    /** @var FileConstraintsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileConstraintsProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileValidator */
    private $fileValidator;

    /** @var FileConstraintFromSystemConfigValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->fileValidator = $this->createMock(FileValidator::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);

        $this->validator = new FileConstraintFromSystemConfigValidator(
            $this->fileValidator,
            $this->fileConstraintsProvider
        );
    }

    public function testInitialize(): void
    {
        $this->fileValidator
            ->expects($this->once())
            ->method('initialize')
            ->with($context = $this->createMock(ExecutionContextInterface::class));

        $this->validator->initialize($context);
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param Constraint $constraint
     * @param int $expectedMaxSize
     * @param array $expectedMimeTypes
     */
    public function testValidate(Constraint $constraint, int $expectedMaxSize, array $expectedMimeTypes): void
    {
        $this->fileConstraintsProvider
            ->method('getMaxSize')
            ->willReturn(self::MAX_SIZE);

        $this->fileConstraintsProvider
            ->method('getMimeTypes')
            ->willReturn(self::MIME_TYPES);

        $this->fileValidator
            ->expects($this->once())
            ->method('validate')
            ->with($file = new \stdClass(), $constraint);

        $this->validator->validate($file, $constraint);

        $this->assertEquals($expectedMaxSize, $constraint->maxSize);
        $this->assertEquals($expectedMimeTypes, $constraint->mimeTypes);
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            'maxsize and mime types are not specified in constraint' => [
                new File(),
                self::MAX_SIZE,
                self::MIME_TYPES,
            ],
            'maxSize is specified in constraint' => [
                new File(['maxSize' => self::MAX_SIZE2]),
                self::MAX_SIZE2,
                self::MIME_TYPES,
            ],
            'maxSize, mimeTypes are specified in constraint' => [
                new File(['maxSize' => self::MAX_SIZE2, 'mimeTypes' => self::MIME_TYPES2]),
                self::MAX_SIZE2,
                self::MIME_TYPES2,
            ],
            'mimeTypes is specified in constraint' => [
                new File(['mimeTypes' => self::MIME_TYPES2]),
                self::MAX_SIZE,
                self::MIME_TYPES2,
            ],
        ];
    }
}
