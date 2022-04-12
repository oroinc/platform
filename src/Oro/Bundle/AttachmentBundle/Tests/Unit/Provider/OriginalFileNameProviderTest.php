<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\OriginalFileNameProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class OriginalFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private const FEATURE_NAME = 'attachment_original_filenames';

    /** @var FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    private OriginalFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new OriginalFileNameProvider($this->innerProvider);
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
