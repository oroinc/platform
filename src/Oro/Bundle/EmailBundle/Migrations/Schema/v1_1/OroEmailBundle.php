<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            sprintf(
                "UPDATE oro_email SET message_id = CONCAT('id.', REPLACE('%s', '-',''), '%s') WHERE message_id IS NULL",
                $this->platform->getGuidExpression(),
                '@bap.migration.generated'
            )
        );

        /** Update table oro_email **/
        $table = $schema->getTable('oro_email');
        $table->changeColumn('message_id', ['notnull' => true]);
        $table->addIndex(['message_id'], 'IDX_email_message_id', []);
        /** End of update table oro_email **/
    }

    /**
     * Sets the database platform
     *
     * @param AbstractPlatform $platform
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }
}
