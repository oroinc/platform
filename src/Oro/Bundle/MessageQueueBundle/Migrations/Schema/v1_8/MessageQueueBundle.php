<?php

namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes indexes from `oro_message_queue` table,
 * Updates json field database type to native JSON
 */
class MessageQueueBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_message_queue');

        $this->dropIndex($table, 'IDX_CC483C0337FDBD6D');
        $this->dropIndex($table, 'IDX_CC483C037FFD7F63');
        $this->dropIndex($table, 'IDX_CC483C0362A6DC27');
        $this->dropIndex($table, 'IDX_CC483C031A065DF8');
        $queries->addPostQuery(new UpdateJsonArrayQuery());
    }

    /**
     * @param Table $table
     * @param string $indexName
     */
    private function dropIndex(Table $table, $indexName)
    {
        if ($table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }
    }
}
