<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Remove unused translations by domain and key prefix.
 */
class DeleteTranslationsByDomainAndKeyPrefixQuery extends ParametrizedMigrationQuery
{
    private string $domain;
    private string $keyPrefix;

    public function __construct(string $domain, string $keyPrefix)
    {
        $this->domain = $domain;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Remove unused translations by domain and key prefix.';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $params = ['domain' => $this->domain, 'key_prefix' => $this->keyPrefix . '%'];
        $types = ['domain' => Types::STRING, 'key_prefix' => Types::STRING];

        $sql = sprintf(
            'DELETE FROM oro_translation_key WHERE domain = :domain AND %s LIKE :key_prefix;',
            $this->connection->getDatabasePlatform() instanceof MySqlPlatform ? '`key`' : 'key'
        );

        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);
    }
}
