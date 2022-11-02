<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager\FileRemoval;

use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\DirectoryExtractor;

class DirectoryExtractorTest extends \PHPUnit\Framework\TestCase
{
    public function testWhenDirectoryMatchExpressionStartsWithInvalidExpression()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The expression must starts with "/^(".'
            . ' Expression: /(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/'
        );

        new DirectoryExtractor('/(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false);
    }

    public function testWhenDirectoryMatchExpressionStartsWithSlash()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The directory match expression must not starts with "/".'
            . ' Expression: /^(\/attachment\/resize\/\d+)\/\d+\/\d+\/\w+/'
        );

        new DirectoryExtractor('/^(\/attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true);
    }

    public function testWhenAllowedToUseForSingleFile()
    {
        $extractor = new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true);
        self::assertTrue($extractor->isAllowedToUseForSingleFile());
    }

    public function testWhenNotAllowedToUseForSingleFile()
    {
        $extractor = new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false);
        self::assertFalse($extractor->isAllowedToUseForSingleFile());
    }

    public function testExtract()
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
