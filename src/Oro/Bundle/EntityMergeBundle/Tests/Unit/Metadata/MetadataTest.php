<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorProvider
     */
    public function testConstruct($options, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            '\Exception',
            $expectedExceptionMessage
        );

        $metadata = new Metadata($options);
    }

    public function constructorProvider()
    {
        return [
            'null'    => [
                'options'                  => null,
                'expectedExceptionMessage' => 'must be of the type array, null given',
            ],
            'bool'    => [
                'options'                  => true,
                'expectedExceptionMessage' => 'must be of the type array, boolean given',
            ],
            'integer' => [
                'options'                  => 2,
                'expectedExceptionMessage' => 'must be of the type array, integer given',
            ],
            'object'  => [
                'options'                  => new \stdClass(),
                'expectedExceptionMessage' => 'must be of the type array, object given',
            ],
            'string'  => [
                'options'                  => 'argument',
                'expectedExceptionMessage' => 'must be of the type array, string given',
            ],
        ];
    }

    public function testGetExistingStrict()
    {
        $metadata = new Metadata(['code' => 'value']);
        $this->assertEquals('value', $metadata->get('code', true));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Option "code" not exists
     */
    public function testGetNonExistingStrict()
    {
        $metadata = new Metadata();
        $this->assertEquals('value', $metadata->get('code', true));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGet($options, $code, $expectedValue)
    {
        $metadata = new Metadata($options);
        $this->assertEquals($expectedValue, $metadata->get($code));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAllWithCallback()
    {
        $options  = [
            'first'  => true,
            'second' => false,
        ];
        $metadata = new Metadata($options);

        $this->assertEquals(
            ['first' => true],
            $metadata->all(
                function ($value) {
                    return (bool)$value;
                }
            )
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMethods($options, $code, $expectedValue, $hasMethod, $isMethod, $isNotExpected = 'assertFalse')
    {
        $metadata = new Metadata($options);
        $metadata->set($code, $expectedValue);
        $this->$hasMethod($metadata->has($code));
        $this->$isMethod($metadata->is($code));
        $this->$isMethod($metadata->is($code, $expectedValue));
        $this->$isNotExpected($metadata->is($code, 'not_expected_value'));
        $this->assertEquals($expectedValue, $metadata->get($code));
        $this->assertEquals(array_merge($options, [$code => $expectedValue]), $metadata->all());
    }

    public function dataProvider()
    {
        return [
            'string'  => [
                'options'       => ['code-string' => 'value-string'],
                'code'          => 'code-string',
                'expectedValue' => 'value-string',
                'hasMethod'     => 'assertTrue',
                'isMethod'      => 'assertTrue',
            ],
            'integer' => [
                'options'       => ['code-integer' => 2],
                'code'          => 'code-integer',
                'expectedValue' => 2,
                'hasMethod'     => 'assertTrue',
                'isMethod'      => 'assertTrue',
            ],
            'bool'    => [
                'options'       => ['code-bool' => true],
                'code'          => 'code-bool',
                'expectedValue' => true,
                'hasMethod'     => 'assertTrue',
                'isMethod'      => 'assertTrue',
                'isNotExpected' => 'assertTrue',
            ],
            'object'  => [
                'options'       => ['code-object' => new \stdClass()],
                'code'          => 'code-object',
                'expectedValue' => new \stdClass(),
                'hasMethod'     => 'assertTrue',
                'isMethod'      => 'assertTrue',
            ],
            'null'    => [
                'options'       => ['code-null' => null],
                'code'          => 'code-null',
                'expectedValue' => null,
                'hasMethod'     => 'assertFalse',
                'isMethod'      => 'assertFalse',
            ],
            'empty'   => [
                'options'       => [],
                'code'          => 'any',
                'expectedValue' => null,
                'hasMethod'     => 'assertFalse',
                'isMethod'      => 'assertFalse',
            ],
            'another' => [
                'options'       => ['code-string' => 'value-string', 'code-bool' => true],
                'code'          => 'code-object',
                'expectedValue' => null,
                'hasMethod'     => 'assertFalse',
                'isMethod'      => 'assertFalse',
            ]
        ];
    }
}
