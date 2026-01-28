<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ObjectManager;

class LoadExpiredAsyncData extends AbstractFixture
{
    public const RECENT_RECORD_NAME = 'async_data_recent';

    private const OUTDATED_RECORDS_COUNT = 101;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $past = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-40 days');

        $this->insertAsyncDataRow($connection, self::RECENT_RECORD_NAME, $now);
        for ($i = 0; $i < self::OUTDATED_RECORDS_COUNT; ++$i) {
            $this->insertAsyncDataRow($connection, 'async_data_outdated_' . $i, $past);
        }
    }

    private function insertAsyncDataRow(Connection $connection, string $name, \DateTime $updatedAt): void
    {
        $connection->createQueryBuilder()
            ->insert('oro_api_async_data')
            ->values([
                'name' => ':name',
                'content' => ':content',
                'updated_at' => ':updatedAt',
                'checksum' => ':checksum',
            ])
            ->setParameter('name', $name, ParameterType::STRING)
            ->setParameter('content', $name . '_content', ParameterType::STRING)
            ->setParameter('updatedAt', $updatedAt->getTimestamp(), ParameterType::INTEGER)
            ->setParameter('checksum', md5($name), ParameterType::STRING)
            ->execute();
    }
}
