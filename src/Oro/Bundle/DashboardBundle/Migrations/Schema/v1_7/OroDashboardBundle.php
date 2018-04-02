<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDashboardBundle implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /**
         * After v1_3 migration
         * Undefined table: 7 ERROR:  relation "oro_dashboard_active_id_seq" does not exist
         */
        if ($this->platform->supportsSequences()) {
            $queries->addPostQuery(
                'ALTER SEQUENCE IF EXISTS oro_dashboard_active_copy_id_seq RENAME TO oro_dashboard_active_id_seq;'
            );
        }
    }
}
