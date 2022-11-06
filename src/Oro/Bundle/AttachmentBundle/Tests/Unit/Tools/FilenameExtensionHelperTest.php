<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;

class FilenameExtensionHelperTest extends \PHPUnit\Framework\TestCase
{
    private FilenameExtensionHelper $filenameExtensionHelper;

    protected function setUp(): void
    {
        $this->filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);
    }

    /**
     * @dataProvider addExtensionDataProvider
     */
    public function testAddExtension(
        string $filename,
        string $extension,
        array $fileMimeTypes,
        string $expectedFilename
    ): void {
        self::assertEquals(
            $expectedFilename,
            $this->filenameExtensionHelper->addExtension($filename, $extension, $fileMimeTypes)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function addExtensionDataProvider(): array
    {
        return [
            [
                'filename' => '',
                'extension' => '',
                'fileMimeTypes' => [],
                'expectedFilename' => '',
            ],
            [
                'filename' => 'sample',
                'extension' => '',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample',
            ],
            [
                'filename' => 'sample',
                'extension' => ' ',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample',
            ],
            [
                'filename' => 'sample',
                'extension' => 'png',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.png',
            ],
            [
                'filename' => 'sample',
                'extension' => ' png ',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.png',
            ],
            [
                'filename' => 'sample.png',
                'extension' => 'png',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.png',
            ],
            [
                'filename' => 'sample.png',
                'extension' => 'PnG',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.png',
            ],
            [
                'filename' => 'sample.png',
                'extension' => 'webp',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.png.webp',
            ],
            [
                'filename' => 'sample.jpg',
                'extension' => 'jpeg',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.jpg',
            ],
            [
                'filename' => 'sample.jpg',
                'extension' => 'JPEG',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.jpg',
            ],
            [
                'filename' => 'sample.jpeg',
                'extension' => 'jpg',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.jpeg',
            ],
            [
                'filename' => 'sample.jpeg',
                'extension' => 'webp',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.jpeg.webp',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => '',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.svg',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => 'svg',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.svg',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => 'webp',
                'fileMimeTypes' => [],
                'expectedFilename' => 'sample.svg',
            ],
            [
                'filename' => '',
                'extension' => '',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => '',
            ],
            [
                'filename' => 'sample',
                'extension' => '',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => 'sample',
            ],
            [
                'filename' => 'sample',
                'extension' => 'svg',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => 'sample',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => '',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => 'sample.svg',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => 'svg',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => 'sample.svg',
            ],
            [
                'filename' => 'sample.svg',
                'extension' => 'webp',
                'fileMimeTypes' => ['image/svg', 'image/png'],
                'expectedFilename' => 'sample.svg',
            ],
        ];
    }

    /**
     * @dataProvider canonicalizeExtensionDataProvider
     */
    public function testCanonicalizeExtension(string $extension, string $expectedExtension): void
    {
        self::assertEquals($expectedExtension, FilenameExtensionHelper::canonicalizeExtension($extension));
    }

    public function canonicalizeExtensionDataProvider(): array
    {
        return [
            ['extension' => '', 'expectedExtension' => ''],
            ['extension' => ' ', 'expectedExtension' => ''],
            ['extension' => 'png', 'expectedExtension' => 'png'],
            ['extension' => ' png ', 'expectedExtension' => 'png'],
            ['extension' => ' pNg ', 'expectedExtension' => 'png'],
            ['extension' => 'jpg', 'expectedExtension' => 'jpg'],
            ['extension' => 'jpeg', 'expectedExtension' => 'jpg'],
        ];
    }
}
