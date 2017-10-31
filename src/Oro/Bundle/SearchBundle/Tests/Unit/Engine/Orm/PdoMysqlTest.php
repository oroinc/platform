<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class PdoMysqlTest extends AbstractPdoTest
{
    public function setUp()
    {
        parent::setUp();

        $this->driver = new PdoMysql();
    }

    /**
     * @dataProvider getJoiAttributesDataProvider
     * @param string|array $fieldName
     * @param string $type
     * @param array $existingAliases
     * @param array $expected
     */
    public function testGetJoinAttributes($fieldName, $type, array $existingAliases, array $expected)
    {
        $this->assertEquals($expected, $this->driver->getJoinAttributes($fieldName, $type, $existingAliases));
    }

    /**
     * @return array
     */
    public function getJoiAttributesDataProvider()
    {
        return [
            [
                'fieldName' => 'testProperty',
                'type' => 'integer',
                'existingAliases' => [],
                'expected' => ['integerFieldtestProperty_1', 'testProperty_1', 1]
            ],
            [
                'fieldName' => 'testProperty',
                'type' => 'string',
                'existingAliases' => [
                    'integerFieldtestProperty_1',
                    'integerFieldtestProperty_2',
                    'stringFieldtestProperty_1',
                    'stringFieldtestProperty_2'
                ],
                'expected' => ['stringFieldtestProperty_3', 'testProperty_3', 3]
            ],
            [
                'fieldName' => ['testPropertyA', 'testPropertyB', 'testPropertyC'],
                'type' => 'string',
                'existingAliases' => [],
                'expected' => [
                    'stringFieldtestPropertyA_testPropertyB_testPropertyC_1',
                    'testPropertyA_testPropertyB_testPropertyC_1',
                    1
                ]
            ],
            [
                'fieldName' => ['testPropertyA', 'testPropertyB', 'testPropertyC'],
                'type' => 'string',
                'existingAliases' => [
                    'integerFieldtestPropertyA_testPropertyB_testPropertyC_1',
                    'integerFieldtestPropertyA_testPropertyB_testPropertyC_2',
                    'stringFieldtestPropertyA_testPropertyB_testPropertyC_1',
                    'stringFieldtestPropertyA_testPropertyB_testPropertyC_2',
                ],
                'expected' => [
                    'stringFieldtestPropertyA_testPropertyB_testPropertyC_3',
                    'testPropertyA_testPropertyB_testPropertyC_3',
                    3
                ]
            ]
        ];
    }
}
