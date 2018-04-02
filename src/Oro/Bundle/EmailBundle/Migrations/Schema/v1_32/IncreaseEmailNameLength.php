<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_32;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class IncreaseEmailNameLength implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::changeEmailFromNameColumnLength($schema);
        static::changeEmailRecipientNameColumnLength($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function changeEmailFromNameColumnLength(Schema $schema)
    {
        $table = $schema->getTable('oro_email');
        if ($table->getColumn('from_name')->getLength() < 320) {
            $table->changeColumn('from_name', ['length' => 320]);
        }
    }

    /**
     * @param Schema $schema
     */
    public static function changeEmailRecipientNameColumnLength(Schema $schema)
    {
        $table = $schema->getTable('oro_email_recipient');
        if ($table->getColumn('name')->getLength() < 320) {
            $table->changeColumn('name', ['length' => 320]);
        }
    }
}
