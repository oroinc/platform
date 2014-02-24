<?php

namespace Oro\Bundle\CronBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class JmsJob implements Migration
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function up(Schema $schema)
    {
        // @codingStandardsIgnoreStart

        /** Generate table jms_job_dependencies **/
        $table = $schema->createTable('jms_job_dependencies');
        $table->addColumn('source_job_id', 'bigint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('dest_job_id', 'bigint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['source_job_id', 'dest_job_id']);
        $table->addIndex(['source_job_id'], 'IDX_8DCFE92CBD1F6B4F', []);
        $table->addIndex(['dest_job_id'], 'IDX_8DCFE92C32CF8D4C', []);
        /** End of generate table jms_job_dependencies **/

        /** Generate table jms_job_related_entities **/
        $table = $schema->createTable('jms_job_related_entities');
        $table->addColumn('job_id', 'bigint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('related_class', 'string', ['default' => null, 'notnull' => true, 'length' => 150, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('related_id', 'string', ['default' => null, 'notnull' => true, 'length' => 100, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['job_id', 'related_class', 'related_id']);
        $table->addIndex(['job_id'], 'IDX_E956F4E2BE04EA9', []);
        /** End of generate table jms_job_related_entities **/

        /** Generate table jms_job_statistics **/
        $table = $schema->createTable('jms_job_statistics');
        $table->addColumn('job_id', 'bigint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('characteristic', 'string', ['default' => null, 'notnull' => true, 'length' => 30, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('createdAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('charValue', 'float', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->setPrimaryKey(['job_id', 'characteristic', 'createdAt']);
        /** End of generate table jms_job_statistics **/

        /** Generate table jms_jobs **/
        $table = $schema->createTable('jms_jobs');
        $table->addColumn('id', 'bigint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => true, 'comment' => '']);
        $table->addColumn('state', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('createdAt', 'datetime', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('startedAt', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('checkedAt', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('executeAfter', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('closedAt', 'datetime', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('command', 'string', ['default' => null, 'notnull' => true, 'length' => 255, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('args', 'json_array', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('output', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('errorOutput', 'text', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('exitCode', 'smallint', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('maxRuntime', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('maxRetries', 'smallint', ['default' => null, 'notnull' => true, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('stackTrace', 'jms_job_safe_object', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => false, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('runtime', 'smallint', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('memoryUsage', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('memoryUsageReal', 'integer', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
        $table->addColumn('originalJob_id', 'bigint', ['default' => null, 'notnull' => false, 'length' => null, 'precision' => 10, 'scale' => 0, 'fixed' => false, 'unsigned' => true, 'autoincrement' => false, 'comment' => '']);
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

        return [];
    }
}
