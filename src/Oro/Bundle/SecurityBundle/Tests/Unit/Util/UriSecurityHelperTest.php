<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Util;

use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;

class UriSecurityHelperTest extends \PHPUnit\Framework\TestCase
{
    private const PROTOCOLS = ['sample-proto1', 'sample-proto2'];

    /** @var UriSecurityHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new UriSecurityHelper(self::PROTOCOLS);
    }

    /**
     * @dataProvider stripDangerousProtocolsDataProvider
     *
     * @param string $uri
     * @param string $expectedUri
     */
    public function testStripDangerousProtocols(string $uri, string $expectedUri): void
    {
        $this->assertEquals($expectedUri, $this->helper->stripDangerousProtocols($uri));
    }

    /**
     * @return array
     */
    public function stripDangerousProtocolsDataProvider(): array
    {
        return [
            [
                'uri' => 'sample-proto1:sample-data',
                'expectedUri' => 'sample-proto1:sample-data',
            ],
            [
                'uri' => 'sAmPlE-pRoTo2:sample-data',
                'expectedUri' => 'sAmPlE-pRoTo2:sample-data',
            ],
            [
                'uri' => 'sample-proto3:sample-data',
                'expectedUri' => 'sample-data',
            ],
        ];
    }

    /**
     * @dataProvider uriHasDangerousProtocolDataProvider
     *
     * @param string $uri
     * @param bool $expected
     */
    public function testUriHasDangerousProtocol(string $uri, bool $expected): void
    {
        $this->assertSame($expected, $this->helper->uriHasDangerousProtocol($uri));
    }

    /**
     * @return array
     */
    public function uriHasDangerousProtocolDataProvider(): array
    {
        return [
            [
                'uri' => 'sample-proto1:sample-data',
                'expected' => false,
            ],
            [
                'uri' => 'sample-proto2:sample-data',
                'expected' => false,
            ],
            [
                'uri' => 'sAmPlE-pRoTo3:sample-data',
                'expected' => true,
            ],
            [
                'uri' => 'sample-proto3:sample-data',
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider isDangerousProtocolDataProvider
     *
     * @param string $protocol
     * @param bool $expected
     */
    public function testIsDangerousProtocol(string $protocol, bool $expected): void
    {
        $this->assertSame($expected, $this->helper->isDangerousProtocol($protocol));
    }

    /**
     * @return array
     */
    public function isDangerousProtocolDataProvider(): array
    {
        return [
            [
                'uri' => 'sample-proto1',
                'expected' => false,
            ],
            [
                'uri' => 'sAmPlE-pRoTo2',
                'expected' => false,
            ],
            [
                'uri' => 'sample-proto3',
                'expected' => true,
            ],
        ];
    }

    public function testGetForbiddenProtocols(): void
    {
        $this->assertEquals(self::PROTOCOLS, $this->helper->getAllowedProtocols());
    }
}
