<?php

namespace Oro\Bundle\ConfigBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroConfigBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_config (id INT AUTO_INCREMENT NOT NULL, entity VARCHAR(255) DEFAULT NULL, record_id INT DEFAULT NULL, UNIQUE INDEX CONFIG_UQ_ENTITY (entity, record_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_config_value (id INT AUTO_INCREMENT NOT NULL, config_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, section VARCHAR(50) DEFAULT NULL, text_value LONGTEXT DEFAULT NULL, object_value LONGTEXT DEFAULT NULL COMMENT '(DC2Type:object)', array_value LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', type VARCHAR(20) NOT NULL, UNIQUE INDEX CONFIG_VALUE_UQ_ENTITY (name, section, config_id), INDEX IDX_DAF6DF5524DB0683 (config_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_config_value ADD CONSTRAINT FK_DAF6DF5524DB0683 FOREIGN KEY (config_id) REFERENCES oro_config (id)"
        ];
    }
}
