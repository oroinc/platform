<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DigitalAssetBundle\Provider\MimeTypesProvider;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DigitalAssetSourceFileValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_SIZE = 10;
    private const MIME_TYPES = ['mime/type1'];
    private const MIME_TYPES2 = ['mime/type2'];

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $systemConfigManager;

    /** @var MimeTypesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypesProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileValidator */
    private $fileValidator;

    /** @var DigitalAssetSourceFileValidator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileValidator = $this->createMock(FileValidator::class);
        $this->systemConfigManager = $this->createMock(ConfigManager::class);
        $this->mimeTypesProvider = $this->createMock(MimeTypesProvider::class);

        $this->validator = new DigitalAssetSourceFileValidator(
            $this->fileValidator,
            $this->systemConfigManager,
            $this->mimeTypesProvider
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
        $this->systemConfigManager
            ->method('get')
            ->with('oro_attachment.maxsize')
            ->willReturn(self::MAX_SIZE);

        $this->mimeTypesProvider
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
            'maxsize and mime types fetched from config' => [
                new File(),
                self::MAX_SIZE * Configuration::BYTES_MULTIPLIER,
                self::MIME_TYPES,
            ],
            'mime types only fetched from config' => [new File(['maxSize' => 1024]), 1024, self::MIME_TYPES],
            'nothing fetched from config' => [
                new File(['maxSize' => 1024, 'mimeTypes' => self::MIME_TYPES2]),
                1024,
                self::MIME_TYPES2,
            ],
            'maxsize only fetched from config' => [
                new File(['mimeTypes' => self::MIME_TYPES2]),
                self::MAX_SIZE * Configuration::BYTES_MULTIPLIER,
                self::MIME_TYPES2,
            ],
        ];
    }
}
