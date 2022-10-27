<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_9;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveMalformedUrlsQuery extends ParametrizedMigrationQuery
{
    private const OLD_MAX_URL_LENGTH = 1023;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $urlFieldName;

    public function __construct(string $tableName, string $urlFieldName)
    {
        $this->tableName = $tableName;
        $this->urlFieldName = $urlFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Removes rows with malformed url from the table after url was truncated';
    }

    /**
     * Ensure that truncated query (because its length could not exceed 1023 characters) is still a valid query
     * (i.e the last % (if present) is followed by 2 characters).
     *
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = <<<'SQL'
DELETE FROM %1$s WHERE LENGTH(%2$s)=:max_length AND (SUBSTRING(%2$s, 1022, 1)='%%' OR SUBSTRING(%2$s, 1023, 1)='%%')
SQL;

        $params = ['max_length' => self::OLD_MAX_URL_LENGTH];

        $this->logQuery($logger, $sql, $params);
        $this->connection->executeQuery(sprintf($sql, $this->tableName, $this->urlFieldName), $params);
    }
}
