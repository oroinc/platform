<?php

declare(strict_types=1);

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Stub\NonPrintableCharsSanitizedSoapClientStub;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Oro\Bundle\IntegrationBundle\Provider\NonPrintableCharsSanitizedSoapClient
 */
class NonPrintableCharsSanitizedSoapClientTest extends TestCase
{
    private const string NULL_BYTE = "\x00";
    private const string UNIT_SEPARATOR = "\x1F";

    private NonPrintableCharsSanitizedSoapClientStub $client;

    protected function setUp(): void
    {
        $this->client = new NonPrintableCharsSanitizedSoapClientStub();
    }

    /**
     * @dataProvider soapCallArgumentsProvider
     */
    public function testSoapCallRemovesNonPrintableCharactersFromArguments(
        array $input,
        array $expected
    ): void {
        $this->client->__soapCall('testMethod', $input);

        $this->assertSame($expected, $this->client->capturedSanitizedArguments);
    }

    public function soapCallArgumentsProvider(): array
    {
        return [
            'simple string with null byte' => [
                ['input' => 'test' . self::NULL_BYTE . 'value'],
                ['input' => 'testvalue'],
            ],
            'nested array with non-printable chars' => [
                [
                    'test' . self::NULL_BYTE . 'value',
                    ['nested' => 'bad' . self::UNIT_SEPARATOR . 'string'],
                ],
                [
                    'testvalue',
                    ['nested' => 'badstring'],
                ],
            ],
            'deeply nested structures' => [
                [
                    [
                        'level1' => [
                            'level2' => [
                                'level3' => 'deep' . self::NULL_BYTE . 'string',
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'level1' => [
                            'level2' => [
                                'level3' => 'deepstring',
                            ],
                        ],
                    ],
                ],
            ],
            'mixed data types' => [
                [
                    'string' . self::NULL_BYTE . 'value',
                    123,
                    45.67,
                    true,
                    null,
                    ['nested' => 'test' . self::UNIT_SEPARATOR . 'value', 'number' => 42],
                ],
                [
                    'stringvalue',
                    123,
                    45.67,
                    true,
                    null,
                    ['nested' => 'testvalue', 'number' => 42],
                ],
            ],
            'empty array' => [
                [],
                [],
            ],
        ];
    }

    /**
     * @dataProvider doRequestResponseProvider
     */
    public function testDoRequestRemovesNonPrintableCharactersFromResponse(
        ?string $mockResponse,
        string $expectedResponse
    ): void {
        $this->client->setMockResponse($mockResponse);

        $response = $this->client->__doRequest(
            '<soap:Envelope/>',
            'http://test.local',
            'testAction',
            SOAP_1_1
        );

        $this->assertSame($expectedResponse, $response);
    }

    public function doRequestResponseProvider(): array
    {
        return [
            'response with null byte' => [
                '<xml>bad' . self::NULL_BYTE . 'response</xml>',
                '<xml>badresponse</xml>',
            ],
            'response with unit separator' => [
                '<xml>test' . self::UNIT_SEPARATOR . 'data</xml>',
                '<xml>testdata</xml>',
            ],
            'null response becomes empty string' => [
                null,
                '',
            ],
            'clean response unchanged' => [
                '<xml>clean</xml>',
                '<xml>clean</xml>',
            ],
        ];
    }
}
