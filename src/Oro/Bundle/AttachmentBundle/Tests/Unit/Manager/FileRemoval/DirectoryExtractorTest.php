<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager\FileRemoval;

use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\DirectoryExtractor;
use PHPUnit\Framework\TestCase;

class DirectoryExtractorTest extends TestCase
{
    public function testWhenDirectoryMatchExpressionStartsWithInvalidExpression(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The expression must starts with "/^(".'
            . ' Expression: /(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/'
        );

        new DirectoryExtractor('/(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false);
    }

    public function testWhenDirectoryMatchExpressionStartsWithSlash(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The directory match expression must not starts with "/".'
            . ' Expression: /^(\/attachment\/resize\/\d+)\/\d+\/\d+\/\w+/'
        );

        new DirectoryExtractor('/^(\/attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true);
    }

    public function testWhenAllowedToUseForSingleFile(): void
    {
        $extractor = new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true);
        self::assertTrue($extractor->isAllowedToUseForSingleFile());
    }

    public function testWhenNotAllowedToUseForSingleFile(): void
    {
        $extractor = new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false);
        self::assertFalse($extractor->isAllowedToUseForSingleFile());
    }

    public function testExtract(): void
    {
        $extractor = new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false);
        self::assertEquals(
            'attachment/resize/123',
            $extractor->extract('attachment/resize/123/10/10/file.jpg')
        );
        self::assertNull($extractor->extract('/attachment/resize/123/10/10/file.jpg'));
        self::assertNull($extractor->extract('attachment/resize/file.jpg'));
    }
}
