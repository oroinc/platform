<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestAllDataTypes;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class SortersByFieldsTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/supported_data_types.yml'
        ]);
    }

    /**
     * @param array  $expectedRows
     * @param string $entityType
     */
    private function prepareExpectedRows(array &$expectedRows, $entityType)
    {
        foreach ($expectedRows as &$row) {
            $row['type'] = $entityType;
        }
    }

    /**
     * @dataProvider sorterDataProvider
     */
    public function testSorter(string $sorter, array $expectedRows)
    {
        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(
            ['entity' => $entityType],
            ['sort' => $sorter . ',id', 'filter[id][neq]' => '<toString(@NullItem->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function sorterDataProvider()
    {
        $expectedRowsDefault = [
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];
        $expectedRowsSortedByBoolean = [
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];
        $expectedRowsSortedByNumber = [
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>']
        ];
        $expectedRowsSortedByDateTime = [
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>']
        ];
        $expectedRowsSortedByDate = [
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@TestItem1->id)>']
        ];
        $expectedRowsSortedByGuid = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>']
        ];
        $expectedRowsSortedByMoney = [
            ['id' => '<toString(@AnotherItem->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>']
        ];
        $expectedRowsSortedByCurrency = [
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];

        return [
            'by string field'      => [
                '-fieldString',
                $expectedRowsDefault
            ],
            'by integer field'     => [
                '-fieldInt',
                $expectedRowsSortedByNumber
            ],
            'by smallint field'    => [
                '-fieldSmallInt',
                $expectedRowsSortedByNumber
            ],
            'by bigint field'      => [
                '-fieldBigInt',
                $expectedRowsDefault
            ],
            'by boolean field'     => [
                '-fieldBoolean',
                $expectedRowsSortedByBoolean
            ],
            'by decimal field'     => [
                '-fieldDecimal',
                $expectedRowsSortedByNumber
            ],
            'by float field'       => [
                '-fieldFloat',
                $expectedRowsSortedByNumber
            ],
            'by datetime field'    => [
                '-fieldDateTime',
                $expectedRowsSortedByDateTime
            ],
            'by date field'        => [
                '-fieldDate',
                $expectedRowsSortedByDate
            ],
            'by time field'        => [
                '-fieldTime',
                $expectedRowsDefault
            ],
            'by guid field'        => [
                '-fieldGuid',
                $expectedRowsSortedByGuid
            ],
            'by percent field'     => [
                '-fieldPercent',
                $expectedRowsDefault
            ],
            'by money field'       => [
                '-fieldMoney',
                $expectedRowsSortedByMoney
            ],
            'by duration field'    => [
                '-fieldDuration',
                $expectedRowsDefault
            ],
            'by money_value field' => [
                '-fieldMoneyValue',
                $expectedRowsSortedByMoney
            ],
            'by currency field'    => [
                '-fieldCurrency',
                $expectedRowsSortedByCurrency
            ]
        ];
    }

    public function testSorterForRenamedField()
    {
        $this->appendEntityConfig(
            TestAllDataTypes::class,
            [
                'fields' => [
                    'renamedField' => [
                        'property_path' => 'fieldString'
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestAllDataTypes::class);
        $expectedRows = [
            ['id' => '<toString(@TestItem3->id)>'],
            ['id' => '<toString(@TestItem2->id)>'],
            ['id' => '<toString(@TestItem1->id)>'],
            ['id' => '<toString(@AnotherItem->id)>']
        ];
        $this->prepareExpectedRows($expectedRows, $entityType);

        $response = $this->cget(
            ['entity' => $entityType],
            ['sort' => '-renamedField', 'filter[id][neq]' => '<toString(@NullItem->id)>']
        );

        $this->assertResponseContains(['data' => $expectedRows], $response);
    }
}
