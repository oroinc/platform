<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_35;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Increase `message_id` column length
 */
class IncreaseMessageIdLength implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email');
        if ($table->getColumn('message_id')->getLength() < 512) {
            $table->changeColumn('message_id', ['length' => 512]);
        }
    }
}
