<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Truncate sanitizing rule processor for an entity.
 */
class TruncateProcessor implements ProcessorInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public static function getProcessorName(): string
    {
        return 'truncate';
    }

    /**
     * {@inheritdoc}
     */
    public function getSqls(ClassMetadata $metadata, array $sanitizeRuleOptions = []): array
    {
        $quotedTableName = $this->connection->quoteIdentifier($metadata->getTableName());

        return ["TRUNCATE $quotedTableName"];
    }
}
