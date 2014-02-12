<?php

namespace Oro\Bundle\ImapBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroImapBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_email_folder_imap (id INT AUTO_INCREMENT NOT NULL, folder_id INT NOT NULL, uid_validity INT NOT NULL, UNIQUE INDEX UNIQ_EC4034F9162CB942 (folder_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_email_imap (id INT AUTO_INCREMENT NOT NULL, email_id INT NOT NULL, uid INT NOT NULL, UNIQUE INDEX UNIQ_17E00D83A832C1C9 (email_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_email_folder_imap ADD CONSTRAINT FK_EC4034F9162CB942 FOREIGN KEY (folder_id) REFERENCES oro_email_folder (id)",
            "ALTER TABLE oro_email_imap ADD CONSTRAINT FK_17E00D83A832C1C9 FOREIGN KEY (email_id) REFERENCES oro_email (id)",

            // Add Imap fields to the oro_email_origin table
            "ALTER TABLE oro_email_origin
                ADD COLUMN imap_host VARCHAR(255) DEFAULT NULL,
                ADD COLUMN imap_port INT DEFAULT NULL,
                ADD COLUMN imap_ssl VARCHAR(3) DEFAULT NULL,
                ADD COLUMN imap_user VARCHAR(100) DEFAULT NULL,
                ADD COLUMN imap_password VARCHAR(100) DEFAULT NULL;",
        ];
    }
}
