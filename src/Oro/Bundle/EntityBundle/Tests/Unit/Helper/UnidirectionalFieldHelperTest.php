<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;

class UnidirectionalFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isFieldUnidirectionalDataProvider
     *
     * @param string $fieldName
     * @param bool $expected
     */
    public function testIsFieldUnidirectional($fieldName, $expected)
    {
        $this->assertEquals($expected, UnidirectionalFieldHelper::isFieldUnidirectional($fieldName));
    }

    /**
     * @return array
     */
    public function isFieldUnidirectionalDataProvider()
    {
        return [
            'unidirectional' => [
                'fieldName' => sprintf('unidirectional%sfield', UnidirectionalFieldHelper::DELIMITER),
                'expected' => true,
            ],
            'not unidirectional' => [
                'fieldName' => 'not_unidirectional_field',
                'expected' => false,
            ],
            'null' => [
                'fieldName' => null,
                'expected' => false,
            ],
            '2 delimiters' => [
                'fieldName' => implode(UnidirectionalFieldHelper::DELIMITER, ['some', 'test', 'field']),
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider getRealFieldNameDataProvider
     *
     * @param string $fieldName
     * @param string $expected
     */
    public function testGetRealFieldName($fieldName, $expected)
    {
        $this->assertEquals($expected, UnidirectionalFieldHelper::getRealFieldName($fieldName));
    }

    /**
     * @return array
     */
    public function getRealFieldNameDataProvider()
    {
        return [
            'unidirectional' => [
                'fieldName' => sprintf('unidirectional%sfield', UnidirectionalFieldHelper::DELIMITER),
                'expected' => 'field',
            ],
            'not unidirectional' => [
                'fieldName' => 'not_unidirectional_field',
                'expected' => 'not_unidirectional_field',
            ],
            'null' => [
                'fieldName' => null,
                'expected' => null,
            ],
            '2 delimiters' => [
                'fieldName' => implode(UnidirectionalFieldHelper::DELIMITER, ['some', 'test', 'field']),
                'expected' => implode(UnidirectionalFieldHelper::DELIMITER, ['some', 'test', 'field']),
            ],
        ];
    }

    /**
     * @dataProvider getRealFieldClassDataProvider
     *
     * @param string $fieldName
     * @param string $expected
     */
    public function testGetRealFieldClass($fieldName, $expected)
    {
        $this->assertEquals($expected, UnidirectionalFieldHelper::getRealFieldClass($fieldName));
    }

    /**
     * @return array
     */
    public function getRealFieldClassDataProvider()
    {
        return [
            'unidirectional' => [
                'fieldName' => sprintf('unidirectional%sfield', UnidirectionalFieldHelper::DELIMITER),
                'expected' => 'unidirectional',
            ],
            'not unidirectional' => [
                'fieldName' => 'not_unidirectional_field',
                'expected' => null,
            ],
            'null' => [
                'fieldName' => null,
                'expected' => null,
            ],
            '2 delimiters' => [
                'fieldName' => implode(UnidirectionalFieldHelper::DELIMITER, ['some', 'test', 'field']),
                'expected' => null,
            ],
        ];
    }
}
