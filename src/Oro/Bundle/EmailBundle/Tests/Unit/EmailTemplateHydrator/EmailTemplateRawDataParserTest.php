<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmailTemplateHydrator;

use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateRawDataParserTest extends TestCase
{
    public function testParseRawDataWithMetadataAndContent(): void
    {
        $rawData = <<<EOT
@name = welcome_email
@subject = Welcome!
@type = html
@isSystem = 1
@attachments = ["file1.txt", "file2.txt"]

Hello {{ user.name }}, welcome!
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertSame('welcome_email', $result['name']);
        self::assertSame('Welcome!', $result['subject']);
        self::assertSame('html', $result['type']);
        self::assertTrue($result['isSystem']);
        self::assertEquals(['file1.txt', 'file2.txt'], $result['attachments']);
        self::assertStringContainsString('Hello {{ user.name }}', $result['content']);
    }

    public function testParseRawDataWithTwigStyleMetadata(): void
    {
        $rawData = <<<EOT
{# @name = reset_password #}
{# @subject = Reset your password #}
{# @type = txt #}

Please reset your password.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertSame('reset_password', $result['name']);
        self::assertSame('Reset your password', $result['subject']);
        self::assertSame('txt', $result['type']);
        self::assertStringContainsString('Please reset your password.', $result['content']);
    }

    public function testParseRawDataWithInvalidArray(): void
    {
        $rawData = <<<EOT
@attachments = not_an_array

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertSame('not_an_array', $result['attachments']);
        self::assertStringContainsString('Content here.', $result['content']);
    }

    public function testParseRawDataWithSingleQuotedFilePlaceholder(): void
    {
        $rawData = <<<EOT
@attachments = ['{{ entity.file }}', '/tmp/file.txt']

Body content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals(['{{ entity.file }}', '/tmp/file.txt'], $result['attachments']);
        self::assertStringContainsString('Body content here.', $result['content']);
    }

    public function testParseRawDataWithMixedQuoteStyles(): void
    {
        $rawData = <<<EOT
@attachments = ["plain.txt", '{{ entity.file }}', "/path/with \"quotes\".pdf", '\/path\/with\/escaped\/slashes.txt']

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals([
            'plain.txt',
            '{{ entity.file }}',
            '/path/with "quotes".pdf',
            '/path/with/escaped/slashes.txt',
        ], $result['attachments']);
    }

    public function testParseRawDataWithNestedArrays(): void
    {
        $rawData = <<<EOT
@complexData = {"files": ["file1.txt", "file2.pdf"], "options": {"type": "attachment"}}

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals([
            'files' => ['file1.txt', 'file2.pdf'],
            'options' => ['type' => 'attachment'],
        ], $result['complexData']);
    }

    public function testParseRawDataWithMalformedJson(): void
    {
        $rawData = <<<EOT
@malformed = [broken"json]

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals(['[broken"json]'], $result['malformed']);
    }

    public function testParseRawDataWithEscapedQuotes(): void
    {
        $rawData = <<<EOT
@attachments = ["file with \\"quoted\\" text.txt", 'file with \\'escaped\\' quotes.pdf']

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals([
            'file with "quoted" text.txt',
            "file with 'escaped' quotes.pdf",
        ], $result['attachments']);
    }

    public function testParseRawDataWithNoMetadata(): void
    {
        $rawData = <<<EOT
This is just content with no metadata.
It should be treated as content only.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertSame($rawData, $result['content']);
        self::assertCount(1, $result);
    }

    public function testParseRawDataWithAttachmentsAsAssociativeArray(): void
    {
        $rawData = <<<EOT
@attachments = {"invoice.pdf": "{{ entity.invoice }}", "terms.txt": "/path/to/terms.txt"}

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertEquals([
            'invoice.pdf' => '{{ entity.invoice }}',
            'terms.txt' => '/path/to/terms.txt',
        ], $result['attachments']);
    }

    public function testParseRawDataWithMetadataValueContainingEquals(): void
    {
        $rawData = <<<EOT
@subject = This = is a subject with = equals signs

Content here.
EOT;

        $parser = new EmailTemplateRawDataParser();
        $result = $parser->parseRawData($rawData);

        self::assertSame('This = is a subject with = equals signs', $result['subject']);
    }
}
