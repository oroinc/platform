<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Decoder;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use PHPUnit\Framework\TestCase;

class ContentDecoderTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDecode(
        string $str,
        ?string $contentTransferEncoding,
        ?string $fromEncode,
        ?string $toEncode,
        string $expected
    ): void {
        $decoder = new ContentDecoder();
        $str = $decoder->decode($str, $contentTransferEncoding, $fromEncode, $toEncode);

        $this->assertEquals($expected, $str);
    }

    public function dataProvider(): array
    {
        return [
            'default' => [
                'string' => 'test',
                'contentTransferEncoding' => null,
                'fromEncode' => null,
                'toEncode' => null,
                'expected' => 'test'
            ],
            'simple base64' => [
                'string' => 'dGVzdA==',
                'contentTransferEncoding' => 'base64',
                'fromEncode' => null,
                'toEncode' => null,
                'expected' => 'test'
            ],
            'simple quoted-printable' => [
                'string' => 'test',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => null,
                'toEncode' => null,
                'expected' => 'test'
            ],
            'UTF-8 quoted-printable' => [
                'string' => '=D1=80=D1=83=D1=80=D1=83bubu',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'UTF-8',
                'toEncode' => 'UTF-8',
                'expected' => 'руруbubu'
            ],
            'UTF-8 with illegal char' => [
                'string' => 'This is the Euro symbol: €.',
                'contentTransferEncoding' => null,
                'fromEncode' => 'UTF-8',
                'toEncode' => 'ISO-8859-1',
                'expected' => 'This is the Euro symbol: EUR.'
            ],
            'UTF-8 base64 with illegal char' => [
                'string' => 'VGhpcyBpcyB0aGUgRXVybyBzeW1ib2w6IOKCrC4=',
                'contentTransferEncoding' => 'base64',
                'fromEncode' => 'UTF-8',
                'toEncode' => 'ISO-8859-1',
                'expected' => 'This is the Euro symbol: EUR.'
            ],
            'UTF-8 quoted-printable with illegal char' => [
                'string' => 'This is the Euro symbol: €.',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'UTF-8',
                'toEncode' => 'ISO-8859-1',
                'expected' => 'This is the Euro symbol: EUR.'
            ],
            'charset alias' => [
                'string' => "\xC7\xD1\xB1\xDB",
                'contentTransferEncoding' => null,
                'fromEncode' => 'ks_c_5601-1987',
                'toEncode' => 'UTF-8',
                'expected' => '한글'
            ],
            'charset alias in upper case' => [
                'string' => "\xC7\xD1\xB1\xDB",
                'contentTransferEncoding' => null,
                'fromEncode' => 'KS_C_5601-1987',
                'toEncode' => 'UTF-8',
                'expected' => '한글'
            ],
            'unknown encoding' => [
                'string' => 'raw content',
                'contentTransferEncoding' => null,
                'fromEncode' => 'x-totally-unknown-charset',
                'toEncode' => 'UTF-8',
                'expected' => 'raw content'
            ],
        ];
    }
}
