<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Psr\Log\LoggerInterface;

class RemoveFieldQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $commandExecutor;

    /** @var  LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var  Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connector;

    /** @var  Statement|\PHPUnit\Framework\MockObject\MockObject */
    protected $statement;

    protected function setUp(): void
    {
        $this->connector = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
        $this->statement = $this->getMockBuilder('\Doctrine\DBAL\Driver\Statement')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testExecuteConfigFieldIsAbsent()
    {
        $migration = new RemoveFieldQuery('TestClassName', 'TestFieldName');
        $migration->setConnection($this->connector);

        $this->connector->expects(self::once())->method('fetchAssoc')->willReturn(null);
        $this->connector->expects(self::never())->method('prepare');
        $migration->execute($this->logger);
    }

    public function testExecute()
    {
        $entityClass = 'TestClassName';
        $fieldName = 'TestFieldName';
        $migration = new RemoveFieldQuery($entityClass, $fieldName);
        $migration->setConnection($this->connector);

        $dbData = 'someDbData';
        $this->connector->expects(self::exactly(2))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1],
                ['data' => $dbData]
            );
        $phpData = [
            'someKey' => 'someData',
            'extend' => [
                'index' => [
                    $fieldName => 'someIndexData',
                    'otherFieldName' => 'otherIndexData',
                ],
                'schema' => [
                    'entity' => 'EX_' . $entityClass,
                    'property' => [
                        $fieldName => 'propertyData',
                        'otherFieldName' => 'otherPropertyData',
                    ],
                    'doctrine' => [
                        'EX_' . $entityClass => [
                            'fields' => [
                                $fieldName => 'someDoctrineData',
                                'otherFieldName' => 'otherDoctrineData',
                            ]
                        ],
                    ],
                ]
            ],
        ];
        $this->connector->expects(self::once())
            ->method('convertToPHPValue')
            ->with($dbData, 'array')
            ->willReturn($phpData);

        $updatedData = [
            'someKey' => 'someData',
            'extend' => [
                'index' => [
                    'otherFieldName' => 'otherIndexData',
                ],
                'schema' => [
                    'entity' => 'EX_' . $entityClass,
                    'property' => [
                        'otherFieldName' => 'otherPropertyData',
                    ],
                    'doctrine' => [
                        'EX_' . $entityClass => [
                            'fields' => [
                                'otherFieldName' => 'otherDoctrineData',
                            ]
                        ],
                    ],
                ]
            ],
        ];
        $toDbData = 'resultDataToDb';
        $this->connector->expects(self::once())
            ->method('convertToDatabaseValue')
            ->with($updatedData, 'array')
            ->willReturn($toDbData);

        $statement1 = $this->createMock(Statement::class);
        $statement1->expects($this->once())
            ->method('execute')
            ->with([1]);
        $statement2 = $this->createMock(Statement::class);
        $statement2->expects($this->once())
            ->method('execute')
            ->with([$toDbData, $entityClass]);
        $this->connector->expects(self::exactly(2))
            ->method('prepare')
            ->withConsecutive(
                ['DELETE FROM oro_entity_config_field WHERE id = ?'],
                ['UPDATE oro_entity_config SET data = ? WHERE class_name = ?']
            )
            ->willReturnOnConsecutiveCalls(
                $statement1,
                $statement2
            );
        $migration->execute($this->logger);
    }
}
