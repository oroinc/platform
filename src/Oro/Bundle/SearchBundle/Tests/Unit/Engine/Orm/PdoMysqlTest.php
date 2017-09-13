<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class PdoMysqlTest extends \PHPUnit_Framework_TestCase
{
    /** @var PdoMysql */
    protected $pdoMysql;

    public function setUp()
    {
        $this->pdoMysql = new PdoMysql();
    }

    /**
     * @dataProvider dataProviderGetUniqueId
     *
     * @param array|string $fieldName
     * @param string $expectedUniqueId
     */
    public function testGetUniqueId($fieldName, $expectedUniqueId)
    {
        $uniqueId = $this->pdoMysql->getUniqueId($fieldName);

        self::assertEquals($expectedUniqueId, $uniqueId);
    }

    /**
     * @dataProvider dataProviderIncrementGetUniqueId
     *
     * @param array|string $fieldName
     * @param string $expectedUniqueIdPrefix
     */
    public function testIncrementGetUniqueId($fieldName, $expectedUniqueIdPrefix)
    {
        for ($i = 1; $i<= 3; $i++) {
            $uniqueId = $this->pdoMysql->getUniqueId($fieldName);
            self::assertEquals($expectedUniqueIdPrefix.$i, $uniqueId);
        }
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
        $this->assertEquals($expected, $this->pdoMysql->getJoinAttributes($fieldName, $type, $existingAliases));
    }

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

    public function dataProviderIncrementGetUniqueId()
    {
        return [
            'empty field Name' => [
                'fieldName' => '',
                'expectedUniqueIdPrefix' => '_',
            ],
            'single filed' => [
                'fieldName' => 'testfieldName',
                'expectedUniqueIdPrefix' => 'testfieldName_'
            ],
            'several fields' => [
                'fieldName' => [
                    'testfieldName',
                    'testfieldName1',
                    'testfieldName2'
                ],
                'expectedUniqueIdPrefix' => 'testfieldName_testfieldName1_testfieldName2_'
            ]
        ];
    }

    public function dataProviderGetUniqueId()
    {
        return [
            'empty field Name' => [
                'fieldName' => '',
                'expectedUniqueId' => '_1',
            ],
            'single filed' => [
                'fieldName' => 'testfieldName',
                'expectedUniqueId' => 'testfieldName_1'
            ],
            'several fields' => [
                'fieldName' => [
                    'testfieldName',
                    'testfieldName1',
                    'testfieldName2'
                ],
                'expectedUniqueId' => 'testfieldName_testfieldName1_testfieldName2_1'
            ]
        ];
    }
}
