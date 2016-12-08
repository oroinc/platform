<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveJmsJob implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('jms_job_dependencies');
        $schema->dropTable('jms_job_related_entities');
        $schema->dropTable('jms_job_statistics');
        $schema->dropTable('jms_jobs');
    }
}
