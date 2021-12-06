<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Provider\Image;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Provider\Image\ConfigImagePlaceholderProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfigImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_KEY = 'oro_test.key';

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private ConfigImagePlaceholderProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->provider = new ConfigImagePlaceholderProvider(
            $this->configManager,
            $this->doctrineHelper,
            $this->attachmentManager,
            self::CONFIG_KEY
        );
    }

    public function testGetPath(): void
    {
        $id = 42;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn($id);

        $image = new File();

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, $id)
            ->willReturn($image);

        $filter = 'image_filter';
        $format = 'sample_format';

        $this->attachmentManager->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($image, $filter, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/path/to/filtered.img');

        self::assertEquals('/path/to/filtered.img', $this->provider->getPath($filter, $format));
    }

    public function testGetPathWithoutConfiguration(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn(null);

        $this->doctrineHelper->expects(self::never())
            ->method(self::anything());

        $this->attachmentManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutFile(): void
    {
        $id = 42;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn($id);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntity')
            ->with(File::class, $id)
            ->willReturn(null);

        $this->attachmentManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getPath('image_filter'));
    }
}
