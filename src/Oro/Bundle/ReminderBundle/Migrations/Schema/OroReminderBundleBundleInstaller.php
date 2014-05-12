<?php

namespace Oro\Bundle\ReminderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ReminderBundle\Migrations\Schema\v1_0\OroReminderBundle;

class OroReminderBundleBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroReminderBundle::oroReminderTable($schema);
    }
}
