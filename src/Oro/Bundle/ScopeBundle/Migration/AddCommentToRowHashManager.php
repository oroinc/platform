<?php

namespace Oro\Bundle\ScopeBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Added comment to row_hash column from which contains the hash
 */
class AddCommentToRowHashManager
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getRelations(): string
    {
        $foreignKeys = $this->doctrine->getManager()
            ->getConnection()
            ->getSchemaManager()
            ->listTableForeignKeys('oro_scope');

        $relations = [];
        foreach ($foreignKeys as $key) {
            $relations[] = strtolower($key->getLocalColumns()[0]);
        }
        sort($relations);

        return implode(',', $relations);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addRowHashComment(Schema $schema): void
    {
        $table = $schema->getTable('oro_scope');
        if (!$table) {
            return;
        }

        $relations = $this->getRelations();
        $table->getColumn('row_hash')->setComment($relations);
    }
}
