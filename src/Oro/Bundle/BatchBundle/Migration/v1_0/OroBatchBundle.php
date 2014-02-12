<?php

namespace Oro\Bundle\BatchBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroBatchBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_batch_job_execution (id INT AUTO_INCREMENT NOT NULL, job_instance_id INT NOT NULL, status INT NOT NULL, start_time DATETIME DEFAULT NULL, end_time DATETIME DEFAULT NULL, create_time DATETIME DEFAULT NULL, updated_time DATETIME DEFAULT NULL, exit_code VARCHAR(255) DEFAULT NULL, exit_description LONGTEXT DEFAULT NULL, failure_exceptions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', log_file VARCHAR(255) DEFAULT NULL, INDEX IDX_66BCFEA7593D6954 (job_instance_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_batch_job_instance (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, `label` VARCHAR(255) DEFAULT NULL, alias VARCHAR(50) NOT NULL, status INT NOT NULL, connector VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, rawConfiguration LONGTEXT NOT NULL COMMENT '(DC2Type:array)', UNIQUE INDEX UNIQ_35B1ECC777153098 (code), PRIMARY KEY(id))",
            "CREATE TABLE oro_batch_mapping_field (id INT AUTO_INCREMENT NOT NULL, item_id INT DEFAULT NULL, source VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, identifier TINYINT(1) NOT NULL, INDEX IDX_45243258126F525E (item_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_batch_mapping_item (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id))",
            "CREATE TABLE oro_batch_step_execution (id INT AUTO_INCREMENT NOT NULL, job_execution_id INT DEFAULT NULL, step_name VARCHAR(100) DEFAULT NULL, status INT NOT NULL, read_count INT NOT NULL, write_count INT NOT NULL, filter_count INT NOT NULL, start_time DATETIME DEFAULT NULL, end_time DATETIME DEFAULT NULL, exit_code VARCHAR(255) DEFAULT NULL, exit_description LONGTEXT DEFAULT NULL, terminate_only TINYINT(1) DEFAULT NULL, failure_exceptions LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', errors LONGTEXT NOT NULL COMMENT '(DC2Type:array)', warnings LONGTEXT NOT NULL COMMENT '(DC2Type:array)', summary LONGTEXT NOT NULL COMMENT '(DC2Type:array)', INDEX IDX_3B30CD3C5871C06B (job_execution_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_batch_job_execution ADD CONSTRAINT FK_66BCFEA7593D6954 FOREIGN KEY (job_instance_id) REFERENCES oro_batch_job_instance (id) ON DELETE CASCADE",
            "ALTER TABLE oro_batch_mapping_field ADD CONSTRAINT FK_45243258126F525E FOREIGN KEY (item_id) REFERENCES oro_batch_mapping_item (id)",
            "ALTER TABLE oro_batch_step_execution ADD CONSTRAINT FK_3B30CD3C5871C06B FOREIGN KEY (job_execution_id) REFERENCES oro_batch_job_execution (id) ON DELETE CASCADE"
        ];
    }
}
