<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_18\RemoveTitleFieldOption;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RemoveTitleFieldOptionTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private Connection&MockObject $connector;

    #[\Override]
    protected function setUp(): void
    {
        $this->connector = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @dataProvider configFieldDataDataProvider
     */
    public function testExecute($phpData, $expectedUpdateCalls): void
    {
        $dbPlatform = $this->createMock(AbstractPlatform::class);
        $migration = new RemoveTitleFieldOption();
        $migration->setConnection($this->connector);

        $dbData = base64_encode(serialize($phpData));
        $this->connector->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([['id' => 1, 'data' => $dbData]]);

        $this->connector->expects(self::once())
            ->method('convertToPHPValue')
            ->with($dbData, 'array')
            ->willReturn($phpData);
        $this->connector->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($dbPlatform);

        $expectedData = [
            'extend' => [
                'owner' => 'Custom',
                'state' => 'Active'
            ],
            'search' => [
                'immutable' => 1,
                'searchable' => 1,
            ]
        ];

        $this->connector->expects(self::exactly($expectedUpdateCalls))
            ->method('executeStatement')
            ->with(
                'UPDATE oro_entity_config_field SET data = :data WHERE id = :id',
                ['data' => $expectedData, 'id' => 1],
                ['id' => 'integer', 'data' => 'array']
            )
            ->willReturn(1);
        $migration->execute($this->logger);
    }

    public function configFieldDataDataProvider()
    {
        $dataWithTitleField = [
            'extend' => [
                'owner' => 'Custom',
                'state' => 'Active'
            ],
            'search' => [
                'immutable' => 1,
                'searchable' => 1,
                'title_field' => 1,
            ]
        ];

        $dataWithoutTitleField = [
            'extend' => [
                'owner' => 'Custom',
                'state' => 'Active'
            ],
            'search' => [
                'immutable' => 1,
                'searchable' => 1,
            ]
        ];

        return [
            [$dataWithTitleField, 1],
            [$dataWithoutTitleField, 0]
        ];
    }
}
