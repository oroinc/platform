<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class JmsJob implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table jms_job_dependencies **/
        $table = $schema->createTable('jms_job_dependencies');
        $table->addColumn('source_job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('dest_job_id', 'bigint', ['unsigned' => true]);
        $table->setPrimaryKey(['source_job_id', 'dest_job_id']);
        $table->addIndex(['source_job_id'], 'IDX_8DCFE92CBD1F6B4F', []);
        $table->addIndex(['dest_job_id'], 'IDX_8DCFE92C32CF8D4C', []);
        /** End of generate table jms_job_dependencies **/

        /** Generate table jms_job_related_entities **/
        $table = $schema->createTable('jms_job_related_entities');
        $table->addColumn('job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('related_class', 'string', ['length' => 150]);
        $table->addColumn('related_id', 'string', ['length' => 100]);
        $table->setPrimaryKey(['job_id', 'related_class', 'related_id']);
        $table->addIndex(['job_id'], 'IDX_E956F4E2BE04EA9', []);
        /** End of generate table jms_job_related_entities **/

        /** Generate table jms_job_statistics **/
        $table = $schema->createTable('jms_job_statistics');
        $table->addColumn('job_id', 'bigint', ['unsigned' => true]);
        $table->addColumn('characteristic', 'string', ['length' => 30]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('charValue', 'float', []);
        $table->setPrimaryKey(['job_id', 'characteristic', 'createdAt']);
        /** End of generate table jms_job_statistics **/

        /** Generate table jms_jobs **/
        $table = $schema->createTable('jms_jobs');
        $table->addColumn('id', 'bigint', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('state', 'string', ['length' => 255]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('startedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('checkedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('executeAfter', 'datetime', ['notnull' => false]);
        $table->addColumn('closedAt', 'datetime', ['notnull' => false]);
        $table->addColumn('command', 'string', ['length' => 255]);
        $table->addColumn('args', 'json_array', []);
        $table->addColumn('output', 'text', ['notnull' => false]);
        $table->addColumn('errorOutput', 'text', ['notnull' => false]);
        $table->addColumn('exitCode', 'smallint', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('maxRuntime', 'smallint', ['unsigned' => true]);
        $table->addColumn('maxRetries', 'smallint', ['unsigned' => true]);
        $table->addColumn('stackTrace', 'jms_job_safe_object', ['notnull' => false]);
        $table->addColumn('runtime', 'smallint', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('memoryUsage', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('memoryUsageReal', 'integer', ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('originalJob_id', 'bigint', ['notnull' => false, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['originalJob_id'], 'IDX_704ADB9349C447F1', []);
        $table->addIndex(['command'], 'IDX_704ADB938ECAEAD4', []);
        $table->addIndex(['executeAfter', 'state'], 'job_runner', []);
        /** End of generate table jms_jobs **/

        /** Generate foreign keys for table jms_job_dependencies **/
        $table = $schema->getTable('jms_job_dependencies');
        $table->addForeignKeyConstraint($schema->getTable('jms_jobs'), ['dest_job_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        $table->addForeignKeyConstraint($schema->getTable('jms_jobs'), ['source_job_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table jms_job_dependencies **/

        /** Generate foreign keys for table jms_job_related_entities **/
        $table = $schema->getTable('jms_job_related_entities');
        $table->addForeignKeyConstraint($schema->getTable('jms_jobs'), ['job_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table jms_job_related_entities **/

        /** Generate foreign keys for table jms_jobs **/
        $table = $schema->getTable('jms_jobs');
        $table->addForeignKeyConstraint($schema->getTable('jms_jobs'), ['originalJob_id'], ['id'], ['onDelete' => null, 'onUpdate' => null]);
        /** End of generate foreign keys for table jms_jobs **/

        // @codingStandardsIgnoreEnd
    }
}
