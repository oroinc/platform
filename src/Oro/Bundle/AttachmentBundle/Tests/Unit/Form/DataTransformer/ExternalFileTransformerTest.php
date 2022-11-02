<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Form\DataTransformer\ExternalFileTransformer;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ExternalFileTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const URL = 'http://example.org/image.png';

    private ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject $configFileValidator;

    private ExternalFileFactory|\PHPUnit\Framework\MockObject\MockObject $externalFileFactory;

    private ExternalFileTransformer $transformer;

    protected function setUp(): void
    {
        $this->configFileValidator = $this->createMock(ConfigFileValidator::class);
        $this->externalFileFactory = $this->createMock(ExternalFileFactory::class);

        $this->transformer = new ExternalFileTransformer($this->configFileValidator, $this->externalFileFactory);
    }

    public function testTransformReturnsEmptyStringWhenNull(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformReturnsValueWhenNotNull(): void
    {
        $value = new ExternalFile('');

        self::assertSame($value, $this->transformer->transform($value));
    }

    public function testReverseTransformReturnsNullWhenNull(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformReturnsOriginalValueWhenNotChanged(): void
    {
        $externalFile = new ExternalFile(self::URL);

        self::assertSame($externalFile, $this->transformer->transform($externalFile));
        self::assertSame($externalFile, $this->transformer->reverseTransform(self::URL));
    }

    public function testReverseTransformReturnsExternalFileWhenNoViolations(): void
    {
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with(self::URL)
            ->willReturn(new ConstraintViolationList());

        $externalFile = new ExternalFile(self::URL, 'image.png');
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->willReturn($externalFile);

        self::assertEquals($externalFile, $this->transformer->reverseTransform(self::URL));
    }

    public function testReverseTransformThrowsExceptionWhenViolations(): void
    {
        $violation = new ConstraintViolation(
            'sample message',
            'sample.template',
            ['sample_key' => 'sample_value'],
            null,
            null,
            self::URL
        );
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with(self::URL)
            ->willReturn(new ConstraintViolationList([$violation]));

        $this->externalFileFactory
            ->expects(self::never())
            ->method('createFromUrl');

        $this->expectExceptionObject(
            new TransformationFailedException(
                $violation->getMessage(),
                0,
                null,
                $violation->getMessageTemplate(),
                $violation->getParameters()
            )
        );

        $this->transformer->reverseTransform(self::URL);
    }

    public function testReverseTransformThrowsExceptionWhenExternalFileNotAccessible(): void
    {
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with(self::URL)
            ->willReturn(new ConstraintViolationList());

        $exception = new ExternalFileNotAccessibleException(
            self::URL,
            'Not Found',
            new \RuntimeException('sample error'),
            $this->createMock(ResponseInterface::class)
        );
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->with(self::URL)
            ->willThrowException($exception);

        $this->expectExceptionObject(
            new TransformationFailedException(
                $exception->getMessage(),
                0,
                $exception,
                'oro.attachment.external_file.invalid_url',
                ['{{ reason }}' => $exception->getReason()]
            )
        );

        $this->transformer->reverseTransform(self::URL);
    }

    public function testReverseTransformReturnsExternalFile(): void
    {
        $this->configFileValidator
            ->expects(self::once())
            ->method('validateExternalFileUrl')
            ->with(self::URL)
            ->willReturn(new ConstraintViolationList());

        $externalFile = new ExternalFile(self::URL, 'image.png');
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->with(self::URL)
            ->willReturn($externalFile);

        self::assertSame($externalFile, $this->transformer->reverseTransform(self::URL));
    }
}
