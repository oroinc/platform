<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Decoder;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;

class ContentDecoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $str
     * @param string $contentTransferEncoding
     * @param string $fromEncode
     * @param string $toEncode
     * @param string $expected
     * @dataProvider dataProvider
     */
    public function testDecode($str, $contentTransferEncoding, $fromEncode, $toEncode, $expected)
    {
        $decoder = new ContentDecoder();
        $str = $decoder->decode($str, $contentTransferEncoding, $fromEncode, $toEncode);

        $this->assertEquals($expected, $str);
    }

    public function dataProvider()
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
            'simple quoted-printable encoded' => [
                'string' => 'test',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'UTF-8',
                'toEncode' => 'windows-1250',
                'expected' => 'test'
            ],
            'UTF-8 quoted-printable' => [
                'string' => '=D1=80=D1=83=D1=80=D1=83bubu',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'UTF-8',
                'toEncode' => 'UTF-8',
                'expected' => 'руруbubu'
            ],
            'koi8-r quoted-printable' => [
                'string' => 'T. &#268;ktesttest',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'koi8-r',
                'toEncode' => 'UTF-8',
                'expected' => 'T. &#268;ktesttest'
            ],
            'windows-1250 quoted-printable' => [
                'string' => '<DIV><FONT face=3DArial=20
size=3D2>khsdkjdsljkdskl=9A=E8=9A=E8=9A=E8=F8=E8=F8=E8=E8=F8ffdssfdsfdsdf=
sdfsdf=E8dffdsd</FONT></DIV>',
                'contentTransferEncoding' => 'quoted-printable',
                'fromEncode' => 'windows-1250',
                'toEncode' => 'UTF-8',
                'expected' => '<DIV><FONT face=Arial 
size=2>khsdkjdsljkdsklščščščřčřččřffdssfdsfdsdfsdfsdfčdffdsd</FONT></DIV>'
            ],
        ];
    }
}
