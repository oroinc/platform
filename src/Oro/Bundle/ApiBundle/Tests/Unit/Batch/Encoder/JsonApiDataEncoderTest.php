<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Encoder;

use Oro\Bundle\ApiBundle\Batch\Encoder\JsonDataEncoder;
use PHPUnit\Framework\TestCase;

class JsonApiDataEncoderTest extends TestCase
{
    private function getEncoder(): JsonDataEncoder
    {
        $encoder = new JsonDataEncoder();
        $encoder->setHeaderSectionName('jsonapi');

        return $encoder;
    }

    public function testEncodeItemsEmptyItems(): void
    {
        self::assertEquals('[]', $this->getEncoder()->encodeItems([]));
    }

    public function testEncode(): void
    {
        $items = [
            [
                'data' => ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']]
            ],
            [
                'data' => ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
            ]
        ];
        $resultJson = '{'
            . '"data":['
            . '{"type":"acme","id":"1","attributes":{"firstName":"FirstName 1"}},'
            . '{"type":"acme","id":"2","attributes":{"firstName":"FirstName 2"}}'
            . ']}';

        self::assertEquals($resultJson, $this->getEncoder()->encodeItems($items));
    }

    public function testEncodeWithHeaderSection(): void
    {
        $items = [
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']]
            ],
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
            ]
        ];
        $resultJson = '{'
            . '"jsonapi":{"version":"1.0"},'
            . '"data":['
            . '{"type":"acme","id":"1","attributes":{"firstName":"FirstName 1"}},'
            . '{"type":"acme","id":"2","attributes":{"firstName":"FirstName 2"}}'
            . ']}';

        self::assertEquals($resultJson, $this->getEncoder()->encodeItems($items));
    }
}
