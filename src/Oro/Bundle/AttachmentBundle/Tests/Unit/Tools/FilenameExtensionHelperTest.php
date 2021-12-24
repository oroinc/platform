<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;

class FilenameExtensionHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider addExtensionDataProvider
     *
     * @param string $filename
     * @param string $extension
     * @param string $expectedFilename
     */
    public function testAddExtension(string $filename, string $extension, string $expectedFilename): void
    {
        self::assertEquals($expectedFilename, FilenameExtensionHelper::addExtension($filename, $extension));
    }

    public function addExtensionDataProvider(): array
    {
        return [
            ['filename' => '', 'extension' => '', 'expectedFilename' => ''],
            ['filename' => 'sample', 'extension' => '', 'expectedFilename' => 'sample'],
            ['filename' => 'sample', 'extension' => ' ', 'expectedFilename' => 'sample'],
            ['filename' => 'sample', 'extension' => 'png', 'expectedFilename' => 'sample.png'],
            ['filename' => 'sample', 'extension' => ' png ', 'expectedFilename' => 'sample.png'],
            ['filename' => 'sample.png', 'extension' => 'png', 'expectedFilename' => 'sample.png'],
            ['filename' => 'sample.png', 'extension' => 'PnG', 'expectedFilename' => 'sample.png'],
            ['filename' => 'sample.png', 'extension' => 'webp', 'expectedFilename' => 'sample.png.webp'],
            ['filename' => 'sample.jpg', 'extension' => 'jpeg', 'expectedFilename' => 'sample.jpg'],
            ['filename' => 'sample.jpg', 'extension' => 'JPEG', 'expectedFilename' => 'sample.jpg'],
            ['filename' => 'sample.jpeg', 'extension' => 'jpg', 'expectedFilename' => 'sample.jpeg'],
            ['filename' => 'sample.jpeg', 'extension' => 'webp', 'expectedFilename' => 'sample.jpeg.webp'],
        ];
    }

    /**
     * @dataProvider canonicalizeExtensionDataProvider
     *
     * @param string $extension
     * @param string $expectedExtension
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
