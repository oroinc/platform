<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;

class FilenameSanitizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider fileNameDataProvider
     */
    public function testSanitizeFilename(string $fileName, string $expected): void
    {
        self::assertEquals($expected, FilenameSanitizer::sanitizeFilename($fileName));
    }

    public function fileNameDataProvider(): array
    {
        return [
            'simple' => ['simple_fileName-1001.jpeg', 'simple_fileName-1001.jpeg'],
            'complex latin' => ['#1@fileName_--1001~~#.jpeg', '1-fileName_-1001.jpeg'],
            'complex mb' => ['~файл#1@fileName_--1001~~#К.jpeg', 'файл-1-fileName_-1001-К.jpeg'],
        ];
    }
}
