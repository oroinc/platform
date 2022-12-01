<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveEnumFieldQueryTest extends TestCase
{
    private LoggerInterface|MockObject $logger;

    private Connection|MockObject $connector;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testExecuteConfigFieldIsAbsent(): void
    {
        $migration = new RemoveEnumFieldQuery('TestClassName', 'TestFieldName');
        $migration->setConnection($this->connector);

        $this->connector->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(null);
        $this->connector->expects(self::never())
            ->method('prepare');

        $migration->execute($this->logger);
    }

    /**
     * @dataProvider enumFieldData
     */
    public function testExecute($entityClass, $fieldName, $enumClass, $extendKey, $fieldPhpData, $entityPhpData): void
    {
        $migration = new RemoveEnumFieldQuery($entityClass, $fieldName);
        $migration->setConnection($this->connector);

        $this->connector->expects(self::exactly(3))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'data' => self::encode($fieldPhpData)],
                ['id' => 2],
                ['data' => self::encode($entityPhpData)]
            );

        $this->connector->expects(self::exactly(2))
            ->method('convertToPHPValue')
            ->withConsecutive(
                [self::encode($fieldPhpData), 'array'],
                [self::encode($entityPhpData), 'array']
            )
            ->willReturnOnConsecutiveCalls($fieldPhpData, $entityPhpData);

        unset(
            $entityPhpData['extend']['relation'][$extendKey],
            $entityPhpData['extend']['schema']['relation'][$fieldName]
        );

        $this->connector->expects(self::once())
            ->method('convertToDatabaseValue')
            ->with($entityPhpData, 'array')
            ->willReturn(self::encode($entityPhpData));

        $statement1 = $this->createMock(Statement::class);
        $statement1->expects($this->once())
            ->method('execute')
            ->with([1]);
        $statement2 = $this->createMock(Statement::class);
        $statement2->expects($this->once())
            ->method('execute')
            ->with([2]);
        $statement3 = $this->createMock(Statement::class);
        $statement3->expects($this->once())
            ->method('execute')
            ->with([$enumClass]);
        $statement4 = $this->createMock(Statement::class);
        $statement4->expects($this->once())
            ->method('execute')
            ->with([self::encode($entityPhpData), $entityClass]);
        $this->connector->expects(self::exactly(4))
            ->method('prepare')
            ->withConsecutive(
                ['DELETE FROM oro_entity_config_field WHERE id = ?'],
                ['DELETE FROM oro_entity_config_field WHERE entity_id = ?'],
                ['DELETE FROM oro_entity_config WHERE class_name = ?'],
                ['UPDATE oro_entity_config SET data = ? WHERE class_name = ?']
            )
            ->willReturnOnConsecutiveCalls(
                $statement1,
                $statement2,
                $statement3,
                $statement4
            );

        $migration->execute($this->logger);
    }

    public function enumFieldData(): array
    {
        $entityClass = 'TestClassName';
        $fieldName = 'TestFieldName';
        $enumClass = 'TestEnumName';
        $extendKey = sprintf('manyToOne|%s|%s|%s', $entityClass, $enumClass, $fieldName);
        $multiEnumExtendKey = sprintf('manyToMany|%s|%s|%s', $entityClass, $enumClass, $fieldName);

        return [
            'Remove Enum Field' => [
                'entityClass' => $entityClass,
                'fieldName' => $fieldName,
                'enumClass' => $enumClass,
                'extendKey' => $extendKey,
                'fieldPhpData' => [
                    'extend' => [
                        'target_entity' => $enumClass
                    ]
                ],
                'entityPhpData' => [
                    'someKey' => 'someData',
                    'extend' => [
                        'relation' => [
                            $extendKey => 'someRelationData',
                            'otherFieldName' => 'otherRelationData',
                        ],
                        'schema' => [
                            'relation' => [
                                $fieldName => 'someRelationData',
                                'otherFieldName' => 'otherPropertyData',
                            ]
                        ]
                    ]
                ]
            ],
            'Remove Multiple Enum Field' => [
                'entityClass' => $entityClass,
                'fieldName' => $fieldName,
                'enumClass' => $enumClass,
                'extendKey' => $multiEnumExtendKey,
                'fieldPhpData' => [
                    'extend' => [
                        'target_entity' => $enumClass
                    ]
                ],
                'entityPhpData' => [
                    'someKey' => 'someData',
                    'extend' => [
                        'relation' => [
                            $multiEnumExtendKey => 'someRelationData',
                            'otherFieldName' => 'otherRelationData',
                        ],
                        'schema' => [
                            'relation' => [
                                $fieldName => 'someRelationData',
                                'otherFieldName' => 'otherPropertyData',
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private static function encode(array $data = []): string
    {
        return base64_encode(serialize($data));
    }
}
