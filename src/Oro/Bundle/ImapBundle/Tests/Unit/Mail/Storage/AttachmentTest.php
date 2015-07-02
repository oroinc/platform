<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Headers;

use Oro\Bundle\ImapBundle\Mail\Storage\Attachment;
use Oro\Bundle\ImapBundle\Mail\Storage\Content;
use Oro\Bundle\ImapBundle\Mail\Storage\Value;

class AttachmentTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $part;

    /** @var Attachment */
    private $attachment;

    protected function setUp()
    {
        $this->part = $this->getMockBuilder('Zend\Mail\Storage\Part')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attachment = new Attachment($this->part);
    }

    public function testGetHeaders()
    {
        $headers = new \stdClass();

        $this->part
            ->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue($headers));

        $result = $this->attachment->getHeaders();

        $this->assertTrue($headers === $result);
    }

    public function testGetHeader()
    {
        $header = new \stdClass();

        $this->part
            ->expects($this->once())
            ->method('getHeader')
            ->with($this->equalTo('SomeHeader'), $this->equalTo('string'))
            ->will($this->returnValue($header));

        $result = $this->attachment->getHeader('SomeHeader', 'string');

        $this->assertTrue($header === $result);
    }

    public function testGetFileNameWithContentDispositionExists()
    {
        $testFileName = 'SomeFile';
        $testEncoding = 'SomeEncoding';

        // Content-Disposition header
        $contentDispositionHeader = $this->getMockBuilder('Zend\Mail\Header\GenericHeader')
            ->disableOriginalConstructor()
            ->getMock();
        $contentDispositionHeader->expects($this->once())
            ->method('getFieldValue')
            ->will($this->returnValue('attachment; filename=' . $testFileName));
        $contentDispositionHeader->expects($this->once())
            ->method('getEncoding')
            ->will($this->returnValue($testEncoding));

        // Headers object
        $headers = $this->getMockBuilder('Zend\Mail\Headers')
            ->disableOriginalConstructor()
            ->getMock();
        $headers->expects($this->once())
            ->method('has')
            ->with($this->equalTo('Content-Disposition'))
            ->will($this->returnValue(true));

        // Part object
        $this->part->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue($headers));
        $this->part
            ->expects($this->once())
            ->method('getHeader')
            ->with($this->equalTo('Content-Disposition'))
            ->will($this->returnValue($contentDispositionHeader));

        $result = $this->attachment->getFileName();

        $expected = new Value($testFileName, $testEncoding);
        $this->assertEquals($expected, $result);
    }

    public function testGetFileNameWithContentDispositionDoesNotExist()
    {
        $testFileName = 'SomeFile';
        $testEncoding = 'SomeEncoding';

        // Content-Disposition header
        $contentTypeHeader = $this->getMockBuilder('Zend\Mail\Header\ContentType')
            ->disableOriginalConstructor()
            ->getMock();
        $contentTypeHeader->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('name'))
            ->will($this->returnValue($testFileName));
        $contentTypeHeader->expects($this->once())
            ->method('getEncoding')
            ->will($this->returnValue($testEncoding));

        // Headers object
        $headers = $this->getMockBuilder('Zend\Mail\Headers')
            ->disableOriginalConstructor()
            ->getMock();
        $headers->expects($this->any())
            ->method('has')
            ->will(
                $this->returnValueMap(
                    [
                        ['Content-Disposition', false],
                        ['Content-Type', true]
                    ]
                )
            );

        // Part object
        $this->part->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue($headers));
        $this->part
            ->expects($this->once())
            ->method('getHeader')
            ->with($this->equalTo('Content-Type'))
            ->will($this->returnValue($contentTypeHeader));

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
        $contentTypeHeader = $this->getMockBuilder('Zend\Mail\Header\ContentType')
            ->disableOriginalConstructor()
            ->getMock();
        if ($contentType !== null) {
            $contentTypeHeader->expects($this->once())
                ->method('getType')
                ->will($this->returnValue($contentType));
            $contentTypeHeader->expects($this->once())
                ->method('getParameter')
                ->with($this->equalTo('charset'))
                ->will($this->returnValue($contentCharset));
        }

        // Content-Transfer-Encoding header
        $contentTransferEncodingHeader = $this->getMockBuilder('Zend\Mail\Header\GenericHeader')
            ->disableOriginalConstructor()
            ->getMock();
        if ($contentTransferEncoding !== null) {
            $contentTransferEncodingHeader->expects($this->once())
                ->method('getFieldValue')
                ->will($this->returnValue($contentTransferEncoding));
        }

        // Headers object
        $headers = $this->getMockBuilder('Zend\Mail\Headers')
            ->disableOriginalConstructor()
            ->getMock();
        $headers->expects($this->any())
            ->method('has')
            ->will(
                $this->returnValueMap(
                    [
                        ['Content-Type', $contentType !== null],
                        ['Content-Transfer-Encoding', $contentTransferEncoding !== null],
                    ]
                )
            );

        // Part object
        $this->part->expects($this->any())
            ->method('getHeaders')
            ->will($this->returnValue($headers));
        $this->part
            ->expects($this->any())
            ->method('getHeader')
            ->will(
                $this->returnValueMap(
                    [
                        ['Content-Type', null, $contentTypeHeader],
                        ['Content-Transfer-Encoding', null, $contentTransferEncodingHeader],
                    ]
                )
            );
        $this->part->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($contentValue));

        $result = $this->attachment->getContent();

        $this->assertEquals($expected, $result);
        $this->assertEquals($decodedValue, $result->getDecodedContent());
    }

    /**
     * @dataProvider getEmbeddedContentIdProvider
     * @param $rawHeaders
     * @param $expected
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

        $this->part
            ->expects($this->any())
            ->method('getHeader')
            ->willReturnMap(
                [
                    ['Content-ID', null, $contentIdHeader],
                    ['Content-Disposition', null, $contentDispositionHeader]
                ]
            );

        $this->part
            ->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);

        $this->assertEquals($expected, $this->attachment->getEmbeddedContentId());
    }

    public static function getEmbeddedContentIdProvider()
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

    public static function getContentProvider()
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
        ];
    }
}
