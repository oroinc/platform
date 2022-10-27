<?php

namespace Oro\Bundle\MessageQueueBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change oro_message_queue_job.data type from deprecated json_array to json.
 */
class ChangeJsonArrayToJsonType implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->platform instanceof PostgreSQL94Platform) {
            $queries->addQuery('ALTER TABLE oro_message_queue_job ALTER COLUMN data TYPE jsonb USING data::jsonb');
        }
    }
}
