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

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var ConfigImagePlaceholderProvider */
    private $provider;

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

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn($id);

        $image = new File();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(File::class, $id)
            ->willReturn($image);

        $filter = 'image_filter';

        $this->attachmentManager->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($image, $filter, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/path/to/filtered.img');

        $this->assertEquals('/path/to/filtered.img', $this->provider->getPath($filter));
    }

    public function testGetPathWithoutConfiguration(): void
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn(null);

        $this->doctrineHelper->expects($this->never())
            ->method($this->anything());

        $this->attachmentManager->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getPath('image_filter'));
    }

    public function testGetPathWithoutFile(): void
    {
        $id = 42;

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_KEY)
            ->willReturn($id);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(File::class, $id)
            ->willReturn(null);

        $this->attachmentManager->expects($this->never())
            ->method($this->anything());

        $this->assertNull($this->provider->getPath('image_filter'));
    }
}
