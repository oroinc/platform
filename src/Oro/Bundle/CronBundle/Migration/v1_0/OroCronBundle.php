<?php

namespace Oro\Bundle\CronBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroCronBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_cron_schedule (id INT AUTO_INCREMENT NOT NULL, command VARCHAR(50) NOT NULL, definition VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UQ_COMMAND (command), PRIMARY KEY(id))"
        ];
    }
}
