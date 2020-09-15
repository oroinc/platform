<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Provider;

use Gaufrette\Adapter\Local;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Configurator\AttachmentSettingsTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Config\FileLocator;

/**
 * @dbIsolationPerTest
 *
 * Check the size of the output file, provided that the input file is not compressed and the height and width of the
 * output file are not larger than the original. Under such conditions, we can guarantee that the output file will be
 * smaller.
 */
class ResizedImageProviderTest extends WebTestCase
{
    use AttachmentSettingsTrait;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->configManager = $this->getContainer()->get('oro_config.global');
    }

    /**
     * @param string $fileName
     */
    public function assertImageSizeShrink(string $fileName): void
    {
        $attachment = $this->getAttachment($fileName);
        [$width, $height] = getimagesizefromstring($attachment->getContent());

        /** @var ResizedImageProvider $resizedImageProvider */
        $resizedImageProvider = $this->getContainer()->get('oro_attachment.provider.resized_image');
        $resizedBinary = $resizedImageProvider->getResizedImage($attachment->getContent(), $width, $height);

        $this->assertLessThan(strlen($attachment->getContent()), strlen($resizedBinary->getContent()));
    }

    /**
     * @param string $fileName
     * @param int $quality
     *
     * @dataProvider pngDataProvider
     */
    public function testGetPNG(string $fileName, int $quality): void
    {
        $this->changeProcessorsParameters(85, $quality);
        $this->configManager->flush();
        $this->assertImageSizeShrink($fileName);
    }

    /**
     * @return string[]
     */
    public function pngDataProvider(): array
    {
        return [
            'PNG attachment RGBA' => [
                'fileName' => 'original_attachment_rgba.png',
                'quality' => 90
            ],
            'PNG attachment RGB' => [
                'fileName' => 'original_attachment_rgb.png',
                'quality' => 50
            ],
            'PNG attachment colormap' => [
                'fileName' => 'original_attachment_colormap.png',
                'quality' => 1
            ],
        ];
    }

    public function testGetJPEG(): void
    {
        $this->changeProcessorsParameters();
        $this->assertImageSizeShrink('original_attachment.jpg');
    }

    /**
     * @param string $fileName
     *
     * @return File
     */
    private function getAttachment(string $fileName): File
    {
        /** @var FileLocator $fileLocator */
        $fileLocator = $this->getContainer()->get('file_locator');
        $attachments = $fileLocator->locate('@OroAttachmentBundle/Tests/Functional/Provider/files/');
        $filesystem = new Filesystem(new Local($attachments, false, 0600));

        return $filesystem->get($fileName);
    }
}
