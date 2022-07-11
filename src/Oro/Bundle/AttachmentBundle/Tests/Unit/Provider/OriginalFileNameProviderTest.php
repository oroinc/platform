<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\OriginalFileNameProvider;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OriginalFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private const FEATURE_NAME = 'attachment_original_filenames';
    private const FILTER = 'sample_filter';
    private const FORMAT = 'sample_format';
    private const WIDTH = 42;
    private const HEIGHT = 142;

    private FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerProvider;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private OriginalFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);

        $this->provider = new OriginalFileNameProvider($this->innerProvider, $filenameExtensionHelper);
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature(self::FEATURE_NAME);
    }

    public function testGetFileNameEmptyOriginalFilename(): void
    {
        $file = new File();
        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

        self::assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFileNameFeatureDisabled(): void
    {
        $file = new File();
        $file->setOriginalFilename('original-filename.jpeg');
        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(false);

        self::assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFileNameFilenameSameAsOriginal(): void
    {
        $fileName = 'original-filename_#123-картинка))).jpeg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        self::assertEquals('original-filename_-123-картинка.jpeg', $this->provider->getFileName($file));
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFileName(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);
        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        self::assertEquals('filename-original-filename_-123-картинка.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFilteredImageEmptyOriginalFilename(): void
    {
        $file = new File();

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetFilteredImageNameFeatureDisabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetFilteredImageNameFilenameSameAsOriginal(): void
    {
        $fileName = 'original-filename_#123-картинка))).jpeg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    public function testGetFilteredImageNameUnsupportedMimeType(): void
    {
        $fileName = 'original-filename_#123-картинка))).svg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setMimeType('image/svg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.svg',
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFilteredImageName(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    public function testGetResizedImageNameEmptyOriginalFilename(): void
    {
        $file = new File();

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getResizedImageName')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameFeatureDisabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getResizedImageName')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameFilenameSameAsOriginal(): void
    {
        $fileName = 'original-filename_#123-картинка))).jpeg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameUnsupportedMimeType(): void
    {
        $fileName = 'original-filename_#123-картинка))).svg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setMimeType('image/svg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.svg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetResizedImageName(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME)
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function getExtensionDataProvider(): array
    {
        return [
            'no extension' => [
                'extension' => null,
            ],
            'extension' => [
                'extension' => 'jpeg',
            ],
        ];
    }
}
