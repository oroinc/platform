<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\PostProcessor;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Imagine\PostProcessor\MetadataServicePostProcessor;
use Oro\Bundle\AttachmentBundle\Provider\MetadataServiceProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MetadataServicePostProcessorTest extends TestCase
{
    private MetadataServiceProvider $metadataServiceProvider;
    private LoggerInterface $logger;
    private MetadataServicePostProcessor $postProcessor;

    protected function setUp(): void
    {
        $this->metadataServiceProvider = $this->createMock(MetadataServiceProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->postProcessor = new MetadataServicePostProcessor(
            $this->metadataServiceProvider,
            $this->logger
        );
    }

    public function testProcessWhenOriginalContentNotProvided(): void
    {
        $binary = $this->createMock(BinaryInterface::class);

        $this->metadataServiceProvider->expects(self::never())
            ->method('isServiceHealthy');
        $this->metadataServiceProvider->expects(self::never())
            ->method('copyMetadata');

        $result = $this->postProcessor->process($binary, []);

        self::assertSame($binary, $result);
    }

    public function testProcessWhenServiceIsNotHealthy(): void
    {
        $binary = $this->createMock(BinaryInterface::class);
        $originalContent = 'original-content';

        $this->metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(false);
        $this->metadataServiceProvider->expects(self::never())
            ->method('copyMetadata');
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Metadata Service is not healthy. Skipping metadata preservation.',
                ['file_name' => null]
            );

        $result = $this->postProcessor->process($binary, ['original_content' => $originalContent]);

        self::assertSame($binary, $result);
    }

    public function testProcessSuccess(): void
    {
        $originalContent = 'original-content';
        $binary = $this->createMock(BinaryInterface::class);
        $binary->expects(self::once())
            ->method('getContent')
            ->willReturn('processed-content');
        $binary->expects(self::once())
            ->method('getMimeType')
            ->willReturn('image/jpeg');
        $binary->expects(self::once())
            ->method('getFormat')
            ->willReturn('jpeg');

        $this->metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(true);
        $this->metadataServiceProvider->expects(self::once())
            ->method('copyMetadata')
            ->with($originalContent, 'processed-content')
            ->willReturn('result-content');
        $this->logger->expects(self::never())
            ->method('warning');

        $result = $this->postProcessor->process($binary, ['original_content' => $originalContent]);

        self::assertInstanceOf(Binary::class, $result);
        self::assertSame('result-content', $result->getContent());
        self::assertSame('image/jpeg', $result->getMimeType());
        self::assertSame('jpeg', $result->getFormat());
    }

    public function testProcessSuccessWithFileName(): void
    {
        $originalContent = 'original-content';
        $fileName = 'test.jpg';
        $binary = $this->createMock(BinaryInterface::class);
        $binary->expects(self::once())
            ->method('getContent')
            ->willReturn('processed-content');
        $binary->expects(self::once())
            ->method('getMimeType')
            ->willReturn('image/jpeg');
        $binary->expects(self::once())
            ->method('getFormat')
            ->willReturn('jpeg');

        $this->metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(true);
        $this->metadataServiceProvider->expects(self::once())
            ->method('copyMetadata')
            ->with($originalContent, 'processed-content')
            ->willReturn('result-content');
        $this->logger->expects(self::never())
            ->method('warning');

        $result = $this->postProcessor->process(
            $binary,
            ['original_content' => $originalContent, 'file_name' => $fileName]
        );

        self::assertInstanceOf(Binary::class, $result);
        self::assertSame('result-content', $result->getContent());
        self::assertSame('image/jpeg', $result->getMimeType());
        self::assertSame('jpeg', $result->getFormat());
    }

    public function testProcessWhenCopyMetadataFails(): void
    {
        $originalContent = 'original-content';
        $fileName = 'test.jpg';
        $binary = $this->createMock(BinaryInterface::class);
        $binary->expects(self::once())
            ->method('getContent')
            ->willReturn('processed-content');

        $this->metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(true);
        $this->metadataServiceProvider->expects(self::once())
            ->method('copyMetadata')
            ->with($originalContent, 'processed-content')
            ->willReturn(null);
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to copy metadata from Metadata Service.',
                self::callback(function ($context) use ($originalContent, $fileName) {
                    return $context['file_name'] === $fileName
                        && $context['source_size'] === strlen($originalContent)
                        && $context['target_size'] === strlen('processed-content');
                })
            );

        $result = $this->postProcessor->process(
            $binary,
            ['original_content' => $originalContent, 'file_name' => $fileName]
        );

        self::assertSame($binary, $result);
    }
}
