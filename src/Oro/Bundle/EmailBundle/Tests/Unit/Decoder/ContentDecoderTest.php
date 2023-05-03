<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Decoder;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;

class ContentDecoderTest extends \PHPUnit\Framework\TestCase
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
    ) {
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
        ];
    }
}
