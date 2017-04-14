<?php
namespace Oro\Bundle\OroMessageQueueBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Component\MessageQueue\Job\Schema as UniqueJobSchema;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroMessageQueueBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createDbalQueueTable($schema);
        $this->createJobTable($schema);
        $this->createUniqueJobTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createDbalQueueTable(Schema $schema)
    {
        $queueSchema = new DbalSchema(
            $this->getDbalConnection(),
            'oro_message_queue'
        );

        $queueSchema->addToSchema($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createUniqueJobTable(Schema $schema)
    {
        $uniqueJobSchema = new UniqueJobSchema(
            $this->getDbalConnection(),
            'oro_message_queue_job_unique'
        );

        $uniqueJobSchema->addToSchema($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createJobTable(Schema $schema)
    {
        $table = $schema->createTable('oro_message_queue_job');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('root_job_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('status', 'string', ['length' => 255]);
        $table->addColumn('interrupted', 'boolean');
        $table->addColumn('`unique`', 'boolean');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('started_at', 'datetime', ['notnull' => false]);
        $table->addColumn('stopped_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('data', 'json_array', [
            'notnull' => false,
            'comment' => '(DC2Type:json_array)',
        ]);
        $table->addColumn('job_progress', 'percent', ['notnull' => false, 'precision' => 0]);
        $table->addForeignKeyConstraint(
            $table,
            ['root_job_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getDbalConnection()
    {
        return $this->container->get('doctrine.dbal.default_connection');
    }
}
