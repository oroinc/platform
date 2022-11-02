<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Provider\WebpAwarePreviewMetadataProvider;

class WebpAwarePreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private PreviewMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerPreviewMetadataProvider;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private WebpAwarePreviewMetadataProvider $provider;

    protected function setUp(): void
    {
        $this->innerPreviewMetadataProvider = $this->createMock(PreviewMetadataProviderInterface::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->provider = new WebpAwarePreviewMetadataProvider(
            $this->innerPreviewMetadataProvider,
            $this->attachmentManager
        );
    }

    public function testGetMetadataReturnsUnchangedWhenNoPreviewElement(): void
    {
        $file = new File();
        $innerMetadata = ['sample_key' => 'sample_value'];
        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata);

        $this->attachmentManager
            ->expects(self::never())
            ->method('isWebpEnabledIfSupported');

        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl');

        self::assertEquals($innerMetadata, $this->provider->getMetadata($file));
    }

    public function testGetMetadataReturnsUnchangedWhenWebpNotEnabledIfSupported(): void
    {
        $file = new File();
        $innerMetadata = ['sample_key' => 'sample_value', 'preview' => '/sample/image.png'];
        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl');

        self::assertEquals($innerMetadata, $this->provider->getMetadata($file));
    }

    public function testGetMetadataReturnsWithPreviewWhenWebpEnabledIfSupported(): void
    {
        $file = new File();
        $innerMetadata = ['sample_key' => 'sample_value', 'preview' => '/sample/image.png'];
        $this->innerPreviewMetadataProvider
            ->expects(self::once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($innerMetadata);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);

        $webpUrl = '/sample/url/img.jpg.webp';
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, 'digital_asset_icon', 'webp')
            ->willReturn($webpUrl);

        self::assertEquals(['preview_webp' => $webpUrl] + $innerMetadata, $this->provider->getMetadata($file));
    }
}
