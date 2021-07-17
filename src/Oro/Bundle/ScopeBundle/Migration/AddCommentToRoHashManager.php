<?php

namespace Oro\Bundle\ScopeBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Added comment to row_hash column from which contains the hash
 */
class AddCommentToRoHashManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getRelations(): string
    {
        $foreignKeys = $this->em->getConnection()->getSchemaManager()->listTableForeignKeys('oro_scope');

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
