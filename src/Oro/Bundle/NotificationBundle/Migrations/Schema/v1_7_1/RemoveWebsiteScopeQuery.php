<?php

namespace Oro\Bundle\NotificationBundle\Migrations\Schema\v1_7_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes website scope.
 */
class RemoveWebsiteScopeQuery extends ParametrizedMigrationQuery
{
    /**
     * {inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    protected function doExecute(LoggerInterface $logger, bool $dryRun = false)
    {
        if (class_exists('Oro\Bundle\WebsiteBundle\OroWebsiteBundle')) {
            return;
        }

        $qb = $this->connection->createQueryBuilder()
            ->from('oro_entity_config')
            ->select('id', 'data');

        $this->logQuery($logger, $qb->getSQL());

        $update = $this->connection->createQueryBuilder()
            ->update('oro_entity_config')
            ->set('data', ':data')
            ->where('id = :id');

        foreach ($qb->execute()->iterateKeyValue() as $id => $data) {
            $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

            if (!isset($data['website'])) {
                continue;
            }
            unset($data['website']);

            $update->setParameter('id', $id, Types::INTEGER)
                ->setParameter('data', $data, Types::ARRAY);

            if ($dryRun) {
                $this->logQuery($logger, $update->getSQL(), $update->getParameters(), $update->getParameterTypes());
            } else {
                $update->execute();
            }
        }
    }
}
