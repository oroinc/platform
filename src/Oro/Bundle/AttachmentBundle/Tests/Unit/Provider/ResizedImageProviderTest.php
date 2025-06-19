<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResizedImageProviderTest extends TestCase
{
    private FileManager&MockObject $fileManager;
    private ImagineBinaryByFileContentFactoryInterface&MockObject $imagineBinaryFactory;
    private ImagineBinaryFilterInterface&MockObject $imagineBinaryFilter;
    private FilterConfiguration&MockObject $filterConfig;
    private FilterRuntimeConfigProviderInterface&MockObject $filterRuntimeConfigProvider;
    private LoggerInterface&MockObject $logger;
    private ResizedImageProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->imagineBinaryFactory = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);
        $this->imagineBinaryFilter = $this->createMock(ImagineBinaryFilterInterface::class);
        $this->filterConfig = $this->createMock(FilterConfiguration::class);
        $this->filterRuntimeConfigProvider = $this->createMock(FilterRuntimeConfigProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ResizedImageProvider(
            $this->fileManager,
            $this->imagineBinaryFactory,
            $this->imagineBinaryFilter,
            $this->filterConfig,
            $this->filterRuntimeConfigProvider,
            $this->logger
        );
    }

    private function getImageFile(): File
    {
        $file = new File();
        ReflectionUtil::setId($file, 1);
        $file->setFilename('test.jpg');

        return $file;
    }

    public function testGetFilteredImageWhenCannotLoadImageContent(): void
    {
        $file = $this->getImageFile();
        $filterName = 'test-filter';

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with(self::identicalTo($file))
            ->willThrowException(new \Exception());
        $this->imagineBinaryFactory->expects(self::never())
            ->method('createImagineBinary');
        $this->imagineBinaryFilter->expects(self::never())
            ->method('applyFilter');
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertNull($this->provider->getFilteredImage($file, $filterName));
    }

    public function testGetFilteredImageWhenCannotApplyFilter(): void
    {
        $file = $this->getImageFile();
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with(self::identicalTo($file))
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willThrowException(new RuntimeException());
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertNull($this->provider->getFilteredImage($file, $filterName, $format));
    }

    public function testGetFilteredImage(): void
    {
        $file = $this->getImageFile();
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with(self::identicalTo($file))
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);
        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getFilteredImage($file, $filterName, $format)
        );
    }

    public function testGetFilteredImageByPathWhenCannotLoadImageContent(): void
    {
        $fileName = 'test.jpg';
        $filterName = 'test-filter';

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with($fileName)
            ->willThrowException(new \Exception());
        $this->imagineBinaryFactory->expects(self::never())
            ->method('createImagineBinary');
        $this->imagineBinaryFilter->expects(self::never())
            ->method('applyFilter');
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertNull($this->provider->getFilteredImageByPath($fileName, $filterName));
    }

    public function testGetFilteredImageByPathWhenCannotApplyFilter(): void
    {
        $fileName = 'test.jpg';
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with($fileName)
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willThrowException(new RuntimeException());
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertNull($this->provider->getFilteredImageByPath($fileName, $filterName, $format));
    }

    public function testGetFilteredImageByPath(): void
    {
        $fileName = 'test.jpg';
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with($fileName)
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);
        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getFilteredImageByPath($fileName, $filterName, $format)
        );
    }

    public function testGetFilteredImageByContentWhenCannotApplyFilter(): void
    {
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willThrowException(new RuntimeException());
        $this->logger->expects(self::once())
            ->method('warning');

        self::assertNull($this->provider->getFilteredImageByContent($imageContent, $filterName, $format));
    }

    public function testGetFilteredImageByContent(): void
    {
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'test-filter';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);
        $this->logger->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getFilteredImageByContent($imageContent, $filterName, $format)
        );
    }

    public function testGetResizedImage(): void
    {
        $file = $this->getImageFile();
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'attachment_10_20';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];
        $width = 10;
        $height = 20;

        $this->filterConfig->expects(self::once())
            ->method('set')
            ->with(
                $filterName,
                ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]
            );

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with(self::identicalTo($file))
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getResizedImage($file, $width, $height, $format)
        );
    }

    public function testGetResizedImageByPath(): void
    {
        $fileName = 'test.jpg';
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'attachment_10_20';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];
        $width = 10;
        $height = 20;

        $this->filterConfig->expects(self::once())
            ->method('set')
            ->with(
                $filterName,
                ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]
            );

        $this->fileManager->expects(self::once())
            ->method('getContent')
            ->with($fileName)
            ->willReturn($imageContent);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getResizedImageByPath($fileName, $width, $height, $format)
        );
    }

    public function testGetResizedImageByContent(): void
    {
        $imageContent = 'test image content';
        $originalImageBinary = $this->createMock(BinaryInterface::class);
        $filteredImageBinary = $this->createMock(BinaryInterface::class);
        $filterName = 'attachment_10_20';
        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_value'];
        $width = 10;
        $height = 20;

        $this->filterConfig->expects(self::once())
            ->method('set')
            ->with(
                $filterName,
                ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]
            );

        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($imageContent)
            ->willReturn($originalImageBinary);
        $this->filterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);
        $this->imagineBinaryFilter->expects(self::once())
            ->method('applyFilter')
            ->with(self::identicalTo($originalImageBinary), $filterName, $runtimeConfig)
            ->willReturn($filteredImageBinary);

        self::assertSame(
            $filteredImageBinary,
            $this->provider->getResizedImageByContent($imageContent, $width, $height, $format)
        );
    }
}
