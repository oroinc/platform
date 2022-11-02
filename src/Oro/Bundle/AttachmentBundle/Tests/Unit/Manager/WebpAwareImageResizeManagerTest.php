<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Manager\WebpAwareImageResizeManager;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

class WebpAwareImageResizeManagerTest extends \PHPUnit\Framework\TestCase
{
    private ImageResizeManagerInterface|\PHPUnit\Framework\MockObject\MockObject $innerImageResizeManager;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpAwareImageResizeManager $manager;

    protected function setUp(): void
    {
        $this->innerImageResizeManager = $this->createMock(ImageResizeManagerInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->manager = new WebpAwareImageResizeManager($this->innerImageResizeManager, $this->webpConfiguration);
    }

    public function testResizeNotCallsWithWebpWhenFormat(): void
    {
        $file = new File();
        $width = 42;
        $height = 4242;
        $format = 'sample_format';
        $forceUpdate = true;
        $binary = new Binary('sample_binary', 'image/png');

        $this->innerImageResizeManager
            ->expects(self::once())
            ->method('resize')
            ->with($file, $width, $height, $format, $forceUpdate)
            ->willReturn($binary);

        self::assertSame($binary, $this->manager->resize($file, $width, $height, $format, $forceUpdate));
    }

    public function testResizeNotCallsWithWebpWhenNoFormatButNotEnabledIfSupported(): void
    {
        $file = new File();
        $width = 42;
        $height = 4242;
        $forceUpdate = true;
        $binary = new Binary('sample_binary', 'image/png');

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(false);

        $this->innerImageResizeManager
            ->expects(self::once())
            ->method('resize')
            ->with($file, $width, $height, '', $forceUpdate)
            ->willReturn($binary);

        self::assertSame($binary, $this->manager->resize($file, $width, $height, '', $forceUpdate));
    }

    public function testResizeCallsWithWebpWhenNoFormatAndEnabledIfSupported(): void
    {
        $file = new File();
        $width = 42;
        $height = 4242;
        $forceUpdate = true;
        $binary1 = new Binary('sample_binary', 'image/png');
        $binary2 = new Binary('sample_binary', 'image/png');

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        $this->innerImageResizeManager
            ->expects(self::exactly(2))
            ->method('resize')
            ->withConsecutive(
                [$file, $width, $height, 'webp', $forceUpdate],
                [$file, $width, $height, '', $forceUpdate]
            )
            ->willReturnOnConsecutiveCalls($binary1, $binary2);

        self::assertSame($binary2, $this->manager->resize($file, $width, $height, '', $forceUpdate));
    }

    public function testApplyFilterNotCallsWithWebpWhenFormat(): void
    {
        $file = new File();
        $filterName = 'filter_name';
        $format = 'sample_format';
        $forceUpdate = true;
        $binary = new Binary('sample_binary', 'image/png');

        $this->innerImageResizeManager
            ->expects(self::once())
            ->method('applyFilter')
            ->with($file, $filterName, $format, $forceUpdate)
            ->willReturn($binary);

        self::assertSame($binary, $this->manager->applyFilter($file, $filterName, $format, $forceUpdate));
    }

    public function testApplyFilterNotCallsWithWebpWhenNoFormatButNotEnabledIfSupported(): void
    {
        $file = new File();
        $filterName = 'filter_name';
        $forceUpdate = true;
        $binary = new Binary('sample_binary', 'image/png');

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(false);

        $this->innerImageResizeManager
            ->expects(self::once())
            ->method('applyFilter')
            ->with($file, $filterName, '', $forceUpdate)
            ->willReturn($binary);

        self::assertSame($binary, $this->manager->applyFilter($file, $filterName, '', $forceUpdate));
    }

    public function testApplyFilterCallsWithWebpWhenNoFormatAndEnabledIfSupported(): void
    {
        $file = new File();
        $filterName = 'filter_name';
        $forceUpdate = true;
        $binary1 = new Binary('sample_binary', 'image/png');
        $binary2 = new Binary('sample_binary', 'image/png');

        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledIfSupported')
            ->willReturn(true);

        $this->innerImageResizeManager
            ->expects(self::exactly(2))
            ->method('applyFilter')
            ->withConsecutive(
                [$file, $filterName, 'webp', $forceUpdate],
                [$file, $filterName, '', $forceUpdate]
            )
            ->willReturnOnConsecutiveCalls($binary1, $binary2);

        self::assertSame($binary2, $this->manager->applyFilter($file, $filterName, '', $forceUpdate));
    }
}
