<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Component\DoctrineUtils\ORM\PlatformResultSetMapping;

class PlatformResultSetMappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider databasePlatformProvider
     *
     * @param AbstractPlatform $platform
     */
    public function testBasicResultSetMapping(AbstractPlatform $platform, $expectedMapping)
    {
        $rsm = new PlatformResultSetMapping($platform);

        $rsm->addEntityResult('Test\Entity', 'entity');

        $rsm->addFieldResult('entity', 'testColumn_1', 'field1');
        $rsm->addScalarResult('testColumn_2', 'testAlias2', 'integer');
        $rsm->setDiscriminatorColumn('testAlias3', 'testColumn_3');
        $rsm->addMetaResult('testAlias4', 'testColumn_4', 'field4', true, 'integer');

        $this->assertTrue($rsm->isFieldResult('testColumn_1'));
        $this->assertFalse($rsm->isScalarResult('testColumn_1'));
        $this->assertEquals('field1', $rsm->getFieldName('testColumn_1'));
        $this->assertFalse($rsm->isFieldResult('testColumn_2'));
        $this->assertTrue($rsm->isScalarResult('testColumn_2'));
        $this->assertEquals('testAlias2', $rsm->getScalarAlias('testColumn_2'));

        $this->assertEquals('Test\Entity', $rsm->getDeclaringClass('testColumn_1'));
        $this->assertEquals('entity', $rsm->getEntityAlias('testColumn_1'));

        $this->assertEquals(
            $expectedMapping,
            [
                'fieldMappings'        => $rsm->fieldMappings,
                'scalarMappings'       => $rsm->scalarMappings,
                'metaMappings'         => $rsm->metaMappings,
                'columnOwnerMap'       => $rsm->columnOwnerMap,
                'discriminatorColumns' => $rsm->discriminatorColumns,
                'declaringClasses'     => $rsm->declaringClasses
            ]
        );
    }

    public function databasePlatformProvider()
    {
        return [
            [
                new MySqlPlatform(),
                [
                    'fieldMappings'        => [
                        'testColumn_1' => 'field1'
                    ],
                    'scalarMappings'       => [
                        'testColumn_2' => 'testAlias2'
                    ],
                    'metaMappings'         => [
                        'testColumn_4' => 'field4'
                    ],
                    'columnOwnerMap'       => [
                        'testColumn_1' => 'entity',
                        'testColumn_3' => 'testAlias3',
                        'testColumn_4' => 'testAlias4'
                    ],
                    'discriminatorColumns' => [
                        'testAlias3' => 'testColumn_3'
                    ],
                    'declaringClasses'     => [
                        'testColumn_1' => 'Test\Entity'
                    ],
                ]
            ],
            [
                new PostgreSqlPlatform(),
                [
                    'fieldMappings'        => [
                        'testcolumn_1' => 'field1'
                    ],
                    'scalarMappings'       => [
                        'testcolumn_2' => 'testAlias2'
                    ],
                    'metaMappings'         => [
                        'testcolumn_4' => 'field4'
                    ],
                    'columnOwnerMap'       => [
                        'testcolumn_1' => 'entity',
                        'testcolumn_3' => 'testAlias3',
                        'testcolumn_4' => 'testAlias4'
                    ],
                    'discriminatorColumns' => [
                        'testAlias3' => 'testcolumn_3'
                    ],
                    'declaringClasses'     => [
                        'testcolumn_1' => 'Test\Entity'
                    ],
                ]
            ],
            [
                new OraclePlatform(),
                [
                    'fieldMappings'        => [
                        'TESTCOLUMN_1' => 'field1'
                    ],
                    'scalarMappings'       => [
                        'TESTCOLUMN_2' => 'testAlias2'
                    ],
                    'metaMappings'         => [
                        'TESTCOLUMN_4' => 'field4'
                    ],
                    'columnOwnerMap'       => [
                        'TESTCOLUMN_1' => 'entity',
                        'TESTCOLUMN_3' => 'testAlias3',
                        'TESTCOLUMN_4' => 'testAlias4'
                    ],
                    'discriminatorColumns' => [
                        'testAlias3' => 'TESTCOLUMN_3'
                    ],
                    'declaringClasses'     => [
                        'TESTCOLUMN_1' => 'Test\Entity'
                    ],
                ]
            ],
        ];
    }
}
