<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Truncate with cascade sanitizing rule processor for an entity.
 */
class TruncateCascadeProcessor implements ProcessorInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public static function getProcessorName(): string
    {
        return 'truncate_cascade';
    }

    /**
     * {@inheritdoc}
     */
    public function getSqls(ClassMetadata $metadata, array $sanitizeRuleOptions = []): array
    {
        $quotedTableName = $this->connection->quoteIdentifier($metadata->getTableName());

        return ["TRUNCATE $quotedTableName CASCADE"];
    }
}
