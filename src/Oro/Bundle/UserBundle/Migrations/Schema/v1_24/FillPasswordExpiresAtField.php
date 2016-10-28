<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_24;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FillPasswordExpiresAtField extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param string $tableName
     */
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->update($this->tableName)
            ->set('password_expires_at', ':expiryDate')
            ->setParameter('expiryDate', new \DateTime('now', new \DateTimeZone('UTC')), Type::DATETIME);
        ;

        $this->logQuery($logger, $qb->getSql());
        if (!$dryRun) {
            $qb->execute();
        }
    }
}
