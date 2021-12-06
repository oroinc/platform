<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\WebpAwareImageFileNamesProvider;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

class WebpAwareImageFileNamesProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileNamesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerFileNamesProvider;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpAwareImageFileNamesProvider $provider;

    protected function setUp(): void
    {
        $this->innerFileNamesProvider = $this->createMock(FileNamesProviderInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->provider = new WebpAwareImageFileNamesProvider($this->innerFileNamesProvider, $this->webpConfiguration);
    }

    public function testGetFileNamesReturnsUnchangedWhenWebpNotEnabledIfSupported(): void
    {
        $file = new File();
        $filenames = ['sample_name1.jpg', 'sample_name2.png'];
        $this->innerFileNamesProvider
            ->expects(self::once())
            ->method('getFileNames')
            ->with($file)
            ->willReturn($filenames);

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(false);

        self::assertEquals($filenames, $this->provider->getFileNames($file));
    }

    public function testGetFileNamesReturnsWithWebpWhenWebpEnabledIfSupported(): void
    {
        $file = new File();
        $filenames = ['sample_name1.jpg', 'sample_name2.webp'];
        $this->innerFileNamesProvider
            ->expects(self::once())
            ->method('getFileNames')
            ->with($file)
            ->willReturn($filenames);

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        self::assertEqualsCanonicalizing(
            ['sample_name1.jpg', 'sample_name1.jpg.webp', 'sample_name2.webp'],
            $this->provider->getFileNames($file)
        );
    }
}
