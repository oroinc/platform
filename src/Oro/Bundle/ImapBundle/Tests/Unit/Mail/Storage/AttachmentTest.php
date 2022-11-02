<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Header\GenericHeader;
use Laminas\Mail\Headers;
use Laminas\Mail\Storage\Part;
use Oro\Bundle\ImapBundle\Mail\Storage\Attachment;
use Oro\Bundle\ImapBundle\Mail\Storage\Content;
use Oro\Bundle\ImapBundle\Mail\Storage\Value;

class AttachmentTest extends \PHPUnit\Framework\TestCase
{
    /** @var Part|\PHPUnit\Framework\MockObject\MockObject */
    private $part;

    /** @var Attachment */
    private $attachment;

    protected function setUp(): void
    {
        $this->part = $this->createMock(Part::class);

        $this->attachment = new Attachment($this->part);
    }

    public function testGetHeaders()
    {
        $headers = new \stdClass();

        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $result = $this->attachment->getHeaders();

        $this->assertSame($headers, $result);
    }

    public function testGetHeader()
    {
        $header = new \stdClass();

        $this->part->expects($this->once())
            ->method('getHeader')
            ->with('SomeHeader', 'string')
            ->willReturn($header);

        $result = $this->attachment->getHeader('SomeHeader', 'string');

        $this->assertSame($header, $result);
    }

    public function testGetFileNameWithContentDispositionExists()
    {
        $testFileName = 'SomeFile';
        $testEncoding = 'SomeEncoding';

        // Content-Disposition header
        $contentDispositionHeader = $this->createMock(GenericHeader::class);
        $contentDispositionHeader->expects($this->once())
            ->method('getFieldValue')
            ->willReturn('attachment; filename=' . $testFileName);
        $contentDispositionHeader->expects($this->once())
            ->method('getEncoding')
            ->willReturn($testEncoding);

        // Headers object
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->once())
            ->method('has')
            ->with('Content-Disposition')
            ->willReturn(true);

        // Part object
        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);
        $this->part->expects($this->once())
            ->method('getHeader')
            ->with('Content-Disposition')
            ->willReturn($contentDispositionHeader);

        $result = $this->attachment->getFileName();

        $expected = new Value($testFileName, $testEncoding);
        $this->assertEquals($expected, $result);
    }

    public function testGetFileNameWithContentDispositionDoesNotExist()
    {
        $testFileName = 'SomeFile';
        $testEncoding = 'SomeEncoding';

        // Content-Disposition header
        $contentTypeHeader = $this->createMock(ContentType::class);
        $contentTypeHeader->expects($this->once())
            ->method('getParameter')
            ->with('name')
            ->willReturn($testFileName);
        $contentTypeHeader->expects($this->once())
            ->method('getEncoding')
            ->willReturn($testEncoding);

        // Headers object
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Disposition', false],
                ['Content-Type', true]
            ]);

        // Part object
        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);
        $this->part->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn($contentTypeHeader);

        $result = $this->attachment->getFileName();

        $expected = new Value($testFileName, $testEncoding);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider getContentProvider
     */
    public function testGetContent(
        $contentTransferEncoding,
        $contentType,
        $contentCharset,
        $contentValue,
        $expected,
        $decodedValue
    ) {
        // Content-Type header
        $contentTypeHeader = $this->createMock(ContentType::class);
        if ($contentType !== null) {
            $contentTypeHeader->expects($this->once())
                ->method('getType')
                ->willReturn($contentType);
            $contentTypeHeader->expects($this->once())
                ->method('getParameter')
                ->with('charset')
                ->willReturn($contentCharset);
        }

        // Headers object
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Type', $contentType !== null],
                ['Content-Transfer-Encoding', $contentTransferEncoding !== null],
            ]);

        // Part object
        $this->part->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);
        $this->part->expects($this->any())
            ->method('getHeader')
            ->willReturnMap([
                ['Content-Type', null, $contentTypeHeader],
                ['Content-Transfer-Encoding', 'array', (array)$contentTransferEncoding],
            ]);
        $this->part->expects($this->once())
            ->method('getContent')
            ->willReturn($contentValue);

        $result = $this->attachment->getContent();

        $this->assertEquals($expected, $result);
        $this->assertEquals($decodedValue, $result->getDecodedContent());
    }

    /**
     * @dataProvider getEmbeddedContentIdProvider
     */
    public function testGetEmbeddedContentId($rawHeaders, $expected)
    {
        $headers = Headers::fromString(implode(PHP_EOL, $rawHeaders), PHP_EOL);

        $contentIdHeader = null;
        $contentDispositionHeader = null;
        if (isset($rawHeaders['Content-ID'])) {
            $contentIdHeader = GenericHeader::fromString($rawHeaders['Content-ID']);
        }
        if (isset($rawHeaders['Content-Disposition'])) {
            $contentDispositionHeader = GenericHeader::fromString($rawHeaders['Content-Disposition']);
        }

        $this->part->expects($this->any())
            ->method('getHeader')
            ->willReturnMap([
                ['Content-ID', null, $contentIdHeader],
                ['Content-Disposition', null, $contentDispositionHeader]
            ]);

        $this->part->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);

        $this->assertEquals($expected, $this->attachment->getEmbeddedContentId());
    }

    public static function getEmbeddedContentIdProvider(): array
    {
        return [
            'embedded content_id from Content-ID Header' => [
                'raw_headers' => [
                    'Content-ID' => sprintf('Content-ID: <%s>', 'test_content_id')
                ],
                'content_id' => 'test_content_id'
            ],
            'embedded content_id from Content-ID Header having Content-Disposition inline header' => [
                'raw_headers' => [
                    'Content-ID' => sprintf('Content-ID: <%s>', 'test_content_id'),
                    'Content-Disposition' => 'Content-Disposition: inline'
                ],
                'content_id' => 'test_content_id'
            ],
            'embedded content_id will be null[no Content-ID]' => [
                'raw_headers' => [
                    'Content-Type' => 'Content-Type: image/png; name="test.png"'
                ],
                'content_id' => null
            ],
            'embedded content_id will be null[no Content-Disposition not inline]' => [
                'raw_headers' => [
                    'Content-ID' => sprintf('Content-ID: <%s>', 'test_content_id'),
                    'Content-Disposition' => 'Content-Disposition: attachment'
                ],
                'content_id' => null
            ],
        ];
    }

    public static function getContentProvider(): array
    {
        return [
            '7bit' => [
                '7Bit',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', '7Bit', 'ISO-8859-1'),
                'A value'
            ],
            '8bit' => [
                '8Bit',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', '8Bit', 'ISO-8859-1'),
                'A value'
            ],
            'binary' => [
                'Binary',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'Binary', 'ISO-8859-1'),
                'A value'
            ],
            'base64' => [
                'Base64',
                'SomeContentType',
                'ISO-8859-1',
                base64_encode('A value'),
                new Content(base64_encode('A value'), 'SomeContentType', 'Base64', 'ISO-8859-1'),
                'A value'
            ],
            'quoted-printable' => [
                'Quoted-Printable',
                'SomeContentType',
                'ISO-8859-1',
                quoted_printable_encode('A value='), // = symbol is added to test the 'quoted printable' decoding
                new Content(quoted_printable_encode('A value='), 'SomeContentType', 'Quoted-Printable', 'ISO-8859-1'),
                'A value='
            ],
            'Unknown' => [
                'Unknown',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'Unknown', 'ISO-8859-1'),
                'A value'
            ],
            'no charset' => [
                '8Bit',
                'SomeContentType',
                null,
                'A value',
                new Content('A value', 'SomeContentType', '8Bit', 'ASCII'),
                'A value'
            ],
            'no Content-Type' => [
                '8Bit',
                null,
                null,
                'A value',
                new Content('A value', 'text/plain', '8Bit', 'ASCII'),
                'A value'
            ],
            'no Content-Transfer-Encoding' => [
                null,
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'BINARY', 'ISO-8859-1'),
                'A value'
            ],
            'multi Content-Transfer-Encoding' => [
                ['8Bit', 'Base64'],
                'SomeContentType',
                'ISO-8859-1',
                base64_encode('A value'),
                new Content(base64_encode('A value'), 'SomeContentType', 'Base64', 'ISO-8859-1'),
                'A value'
            ]
        ];
    }
}
