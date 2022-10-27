<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class MimeTypeCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isImageMimeTypeDataProvider
     */
    public function testIsImageMimeType(string $mimeType, bool $expectedResult): void
    {
        $typeGuesser = new MimeTypeChecker($configManager = $this->createMock(ConfigManager::class));

        $configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types')
            ->willReturn($mimeTypes = 'ext1' . PHP_EOL . 'ext2');

        self::assertSame($expectedResult, $typeGuesser->isImageMimeType($mimeType));
    }

    public function isImageMimeTypeDataProvider(): array
    {
        return [
            [
                'mimeType' => 'ext1',
                'expectedResult' => true,
            ],
            [
                'mimeType' => 'unknown',
                'expectedResult' => false,
            ],
        ];
    }
}
