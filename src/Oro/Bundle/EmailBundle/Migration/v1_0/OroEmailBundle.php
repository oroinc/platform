<?php

namespace Oro\Bundle\EmailBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroEmailBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_email (id INT AUTO_INCREMENT NOT NULL, from_email_address_id INT NOT NULL, folder_id INT DEFAULT NULL, created DATETIME NOT NULL, subject VARCHAR(500) NOT NULL, from_name VARCHAR(255) NOT NULL, received DATETIME NOT NULL, sent DATETIME NOT NULL, importance INT NOT NULL, internaldate DATETIME NOT NULL, message_id VARCHAR(255) DEFAULT NULL, x_message_id VARCHAR(255) DEFAULT NULL, x_thread_id VARCHAR(255) DEFAULT NULL, INDEX IDX_2A30C171162CB942 (folder_id), INDEX IDX_2A30C171D445573A (from_email_address_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_address (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, email VARCHAR(255) NOT NULL, has_owner TINYINT(1) NOT NULL, UNIQUE INDEX oro_email_address_uq (email), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_attachment (id INT AUTO_INCREMENT NOT NULL, body_id INT DEFAULT NULL, file_name VARCHAR(255) NOT NULL, content_type VARCHAR(100) NOT NULL, INDEX IDX_F4427F239B621D84 (body_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_attachment_content (id INT AUTO_INCREMENT NOT NULL, attachment_id INT NOT NULL, content LONGTEXT NOT NULL, content_transfer_encoding VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_18704959464E68B (attachment_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_body (id INT AUTO_INCREMENT NOT NULL, email_id INT DEFAULT NULL, created DATETIME NOT NULL, body LONGTEXT NOT NULL, body_is_text TINYINT(1) NOT NULL, has_attachments TINYINT(1) NOT NULL, persistent TINYINT(1) NOT NULL, INDEX IDX_C7CE120DA832C1C9 (email_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_folder (id INT AUTO_INCREMENT NOT NULL, origin_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, type VARCHAR(10) NOT NULL, synchronized DATETIME DEFAULT NULL, INDEX IDX_EB940F1C56A273CC (origin_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_origin (id INT AUTO_INCREMENT NOT NULL, isActive TINYINT(1) NOT NULL, sync_code_updated DATETIME DEFAULT NULL, synchronized DATETIME DEFAULT NULL, sync_code INT DEFAULT NULL, name VARCHAR(30) NOT NULL, internal_name VARCHAR(30) DEFAULT NULL, PRIMARY KEY(id))",
            "CREATE TABLE oro_email_recipient (id INT AUTO_INCREMENT NOT NULL, email_address_id INT NOT NULL, email_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(3) NOT NULL, INDEX IDX_7DAF9656A832C1C9 (email_id), INDEX IDX_7DAF965659045DAA (email_address_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_template (id INT AUTO_INCREMENT NOT NULL, isSystem TINYINT(1) NOT NULL, isEditable TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, parent INT DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, entityName VARCHAR(255) DEFAULT NULL, type VARCHAR(20) NOT NULL, UNIQUE INDEX UQ_NAME (name, entityName), INDEX email_name_idx (name), INDEX email_is_system_idx (isSystem), INDEX email_entity_name_idx (entityName), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_template_translation (id INT AUTO_INCREMENT NOT NULL, object_id INT DEFAULT NULL, locale VARCHAR(8) NOT NULL, field VARCHAR(32) NOT NULL, content LONGTEXT DEFAULT NULL, INDEX IDX_F42DCDB8232D562B (object_id), INDEX lookup_unique_idx (locale, object_id, field), PRIMARY KEY(id))",

            "ALTER TABLE oro_email ADD CONSTRAINT FK_2A30C171D445573A FOREIGN KEY (from_email_address_id) REFERENCES oro_email_address (id)",
            "ALTER TABLE oro_email ADD CONSTRAINT FK_2A30C171162CB942 FOREIGN KEY (folder_id) REFERENCES oro_email_folder (id)",
            "ALTER TABLE oro_email_attachment ADD CONSTRAINT FK_F4427F239B621D84 FOREIGN KEY (body_id) REFERENCES oro_email_body (id)",
            "ALTER TABLE oro_email_attachment_content ADD CONSTRAINT FK_18704959464E68B FOREIGN KEY (attachment_id) REFERENCES oro_email_attachment (id)",
            "ALTER TABLE oro_email_body ADD CONSTRAINT FK_C7CE120DA832C1C9 FOREIGN KEY (email_id) REFERENCES oro_email (id)",
            "ALTER TABLE oro_email_folder ADD CONSTRAINT FK_EB940F1C56A273CC FOREIGN KEY (origin_id) REFERENCES oro_email_origin (id)",
            "ALTER TABLE oro_email_recipient ADD CONSTRAINT FK_7DAF965659045DAA FOREIGN KEY (email_address_id) REFERENCES oro_email_address (id)",
            "ALTER TABLE oro_email_recipient ADD CONSTRAINT FK_7DAF9656A832C1C9 FOREIGN KEY (email_id) REFERENCES oro_email (id)",
            "ALTER TABLE oro_email_template_translation ADD CONSTRAINT FK_F42DCDB8232D562B FOREIGN KEY (object_id) REFERENCES oro_email_template (id) ON DELETE CASCADE"
        ];
    }
}
