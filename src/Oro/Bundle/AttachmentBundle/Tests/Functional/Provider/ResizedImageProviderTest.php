<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Provider;

use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Configurator\AttachmentSettingsTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
    }

    private function getFileManager(): FileManager
    {
        return self::getContainer()->get('oro_attachment.file_manager');
    }

    private function getResizedImageProvider(): ResizedImageProviderInterface
    {
        return self::getContainer()->get('oro_attachment.provider.resized_image');
    }

    private function loadImage(string $fileName): array
    {
        $data = file_get_contents(__DIR__ . '/files/' . $fileName);
        [$width, $height] = getimagesizefromstring($data);

        return [$data, $width, $height];
    }

    private function assertImageSizeShrink(string $fileName): void
    {
        [$data, $width, $height] = $this->loadImage($fileName);

        $fileManager = $this->getFileManager();
        $fileManager->deleteFile($fileName);
        try {
            $fileManager->writeToStorage($data, $fileName);
            $resizedBinary = $this->getResizedImageProvider()->getResizedImageByPath($fileName, $width, $height);
        } finally {
            $fileManager->deleteFile($fileName);
        }

        self::assertLessThan(strlen($data), strlen($resizedBinary->getContent()));
    }

    /**
     * @dataProvider pngDataProvider
     */
    public function testGetPNG(string $fileName, int $quality): void
    {
        $this->changeProcessorsParameters(85, $quality);
        self::getConfigManager()->flush();
        $this->assertImageSizeShrink($fileName);
    }

    public function pngDataProvider(): array
    {
        return [
            'PNG attachment RGBA'     => [
                'fileName' => 'original_attachment_rgba.png',
                'quality'  => 90
            ],
            'PNG attachment RGB'      => [
                'fileName' => 'original_attachment_rgb.png',
                'quality'  => 50
            ],
            'PNG attachment colormap' => [
                'fileName' => 'original_attachment_colormap.png',
                'quality'  => 1
            ]
        ];
    }

    public function testGetJPEG(): void
    {
        $this->changeProcessorsParameters();
        $this->assertImageSizeShrink('original_attachment.jpg');
    }

    public function testResizedImageByFileContent(): void
    {
        $this->changeProcessorsParameters();

        [$data, $width, $height] = $this->loadImage('original_attachment.jpg');
        $resizedBinary = $this->getResizedImageProvider()->getResizedImageByContent($data, $width, $height);
        self::assertLessThan(strlen($data), strlen($resizedBinary->getContent()));
    }
}
