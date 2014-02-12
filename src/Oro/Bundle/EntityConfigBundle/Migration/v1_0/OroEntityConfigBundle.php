<?php

namespace Oro\Bundle\EntityConfigBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroEntityConfigBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_entity_config (id INT AUTO_INCREMENT NOT NULL, class_name VARCHAR(255) NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, mode VARCHAR(8) NOT NULL, UNIQUE INDEX oro_entity_config_uq (class_name), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_field (id INT AUTO_INCREMENT NOT NULL, entity_id INT DEFAULT NULL, field_name VARCHAR(255) NOT NULL, type VARCHAR(60) NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, mode VARCHAR(8) NOT NULL, INDEX IDX_63EC23F781257D5D (entity_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, logged_at DATETIME NOT NULL, INDEX IDX_4A4961FBA76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_log_diff (id INT AUTO_INCREMENT NOT NULL, log_id INT DEFAULT NULL, class_name VARCHAR(100) NOT NULL, field_name VARCHAR(100) DEFAULT NULL, scope VARCHAR(100) DEFAULT NULL, diff LONGTEXT NOT NULL, INDEX IDX_D1F6D75AEA675D86 (log_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_optionset (id INT AUTO_INCREMENT NOT NULL, field_id INT DEFAULT NULL, `label` VARCHAR(255) DEFAULT NULL, priority SMALLINT DEFAULT NULL, is_default TINYINT(1) DEFAULT NULL, INDEX IDX_CDC152C4443707B0 (field_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_optionset_relation (id INT AUTO_INCREMENT NOT NULL, option_id INT DEFAULT NULL, field_id INT DEFAULT NULL, entity_id INT NOT NULL, INDEX IDX_797D3D83443707B0 (field_id), INDEX IDX_797D3D83A7C41D6F (option_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_entity_config_value (id INT AUTO_INCREMENT NOT NULL, field_id INT DEFAULT NULL, entity_id INT DEFAULT NULL, code VARCHAR(255) NOT NULL, scope VARCHAR(255) NOT NULL, value LONGTEXT DEFAULT NULL, serializable TINYINT(1) NOT NULL, INDEX IDX_256E3E9B81257D5D (entity_id), INDEX IDX_256E3E9B443707B0 (field_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_entity_config_log_diff ADD CONSTRAINT FK_D1F6D75AEA675D86 FOREIGN KEY (log_id) REFERENCES oro_entity_config_log (id) ON DELETE CASCADE",
            "ALTER TABLE oro_entity_config_optionset ADD CONSTRAINT FK_CDC152C4443707B0 FOREIGN KEY (field_id) REFERENCES oro_entity_config_field (id)",
            "ALTER TABLE oro_entity_config_optionset_relation ADD CONSTRAINT FK_797D3D83A7C41D6F FOREIGN KEY (option_id) REFERENCES oro_entity_config_optionset (id)",
            "ALTER TABLE oro_entity_config_optionset_relation ADD CONSTRAINT FK_797D3D83443707B0 FOREIGN KEY (field_id) REFERENCES oro_entity_config_field (id)",
            "ALTER TABLE oro_entity_config_value ADD CONSTRAINT FK_256E3E9B443707B0 FOREIGN KEY (field_id) REFERENCES oro_entity_config_field (id)",
            "ALTER TABLE oro_entity_config_value ADD CONSTRAINT FK_256E3E9B81257D5D FOREIGN KEY (entity_id) REFERENCES oro_entity_config (id)",
            "ALTER TABLE oro_entity_config_field ADD CONSTRAINT FK_63EC23F781257D5D FOREIGN KEY (entity_id) REFERENCES oro_entity_config (id)",
            "ALTER TABLE oro_entity_config_log ADD CONSTRAINT FK_4A4961FBA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE"
        ];
    }
}
