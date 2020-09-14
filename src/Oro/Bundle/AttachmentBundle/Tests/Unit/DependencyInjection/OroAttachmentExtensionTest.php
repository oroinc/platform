<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection;

use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAttachmentExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $configuration;

    public function testLoad(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroAttachmentExtension();
        $configs = [];
        $isCalled = false;
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects($this->any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$isCalled) {
                if ($name == 'oro_attachment.files' && is_array($value)) {
                    $isCalled = true;
                }
            });

        $extension->load($configs, $container);
        $this->assertTrue($isCalled);
    }

    public function testLoadParameters(): void
    {
        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroAttachmentExtension();
        $configs = [];

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(16))
            ->method('setParameter')
            ->withConsecutive(
                ['oro_attachment.filesystem_dir.attachments', 'attachment'],
                ['oro_attachment.filesystem_name.attachments', 'attachments'],
                ['oro_attachment.filesystem_name.mediacache', 'mediacache'],
                ['oro_attachment.filesystem_dir.mediacache', 'media/cache'],
                ['oro_attachment.filesystem_name.protected_mediacache', 'protected_mediacache'],
                ['oro_attachment.filesystem_dir.protected_mediacache', 'attachment/cache'],
                ['oro_attachment.liip_imagine.unsupported_mime_types', ['image/svg+xml']],
                ['oro_attachment.provider.resized_image_path.skip_prefix', 'media/cache'],
                ['oro_attachment.import_files_dir', '%kernel.project_dir%/var/import_export/files/'],
                ['oro_attachment.debug_images', true],
                ['oro_attachment.upload_file_mime_types', []],
                ['oro_attachment.upload_image_mime_types', []],
                ['oro_attachment.processors_allowed', true],
                ['oro_attachment.png_quality', 100],
                ['oro_attachment.jpeg_quality', 85],
                ['oro_attachment.files', $this->getAttachmentFiles()]
            );

        $extension->load($configs, $container);
    }

    private function getAttachmentFiles(): array
    {
        return [
            'default' => 'fa-file-o',
            'doc' => 'fa-file-text-o',
            'docx' => 'fa-file-text-o',
            'xls' => 'fa-file-excel-o',
            'xlsx' => 'fa-file-excel-o',
            'pdf' => 'fa-file-pdf-o',
            'png' => 'fa-file-image-o',
            'jpg' => 'fa-file-image-o',
            'jpeg' => 'fa-file-image-o',
            'gif' => 'fa-file-image-o',
            'mp4' => 'fa-file-movie-o'
        ];
    }
}
