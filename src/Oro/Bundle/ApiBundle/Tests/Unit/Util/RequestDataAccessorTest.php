<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\RequestDataAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

class RequestDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    private RequestDataAccessor $requestDataAccessor;

    protected function setUp(): void
    {
        $this->requestDataAccessor = new RequestDataAccessor();
    }

    public function testGetValue()
    {
        $requestData = [
            [
                'key1' => 'value1',
                [
                    'key2' => 'value2'
                ]
            ]
        ];

        self::assertSame('value1', $this->requestDataAccessor->getValue($requestData, '0.key1'));
        self::assertSame('value2', $this->requestDataAccessor->getValue($requestData, '0.0.key2'));
    }

    public function testGetValueForNotExistingIndex()
    {
        $this->expectException(NoSuchIndexException::class);
        $requestData = ['key1' => 'value1'];

        $this->requestDataAccessor->getValue($requestData, 'key2');
    }

    public function testGetValueWhenValueWithinPathIsNotArray()
    {
        $this->expectException(UnexpectedTypeException::class);
        $requestData = ['key1' => 'value1'];

        $this->requestDataAccessor->getValue($requestData, 'key1.key2');
    }

    public function testSetValue()
    {
        $requestData = [
            [
                'key1' => 'value1',
                [
                    'key2' => 'value2'
                ]
            ]
        ];

        $this->requestDataAccessor->setValue($requestData, '0.key1', 'new_value1');
        $this->requestDataAccessor->setValue($requestData, '0.0.key2', 'new_value2');
        $this->requestDataAccessor->setValue($requestData, '0.key3', 'value3');

        self::assertSame(
            [
                [
                    'key1' => 'new_value1',
                    [
                        'key2' => 'new_value2'
                    ],
                    'key3' => 'value3'
                ]
            ],
            $requestData
        );
    }

    public function testSetValueWhenValueWithinPathIsNotArray()
    {
        $this->expectException(UnexpectedTypeException::class);
        $requestData = ['key1' => 'value1'];

        $this->requestDataAccessor->setValue($requestData, 'key1.key2', 'val');
    }
}
