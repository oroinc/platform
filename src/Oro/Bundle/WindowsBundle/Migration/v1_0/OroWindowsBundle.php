<?php

namespace Oro\Bundle\WindowsBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroWindowsBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_windows_state (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_8B134CF6A76ED395 (user_id), PRIMARY KEY(id))",
            "ALTER TABLE oro_windows_state ADD CONSTRAINT FK_8B134CF6A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE"
        ];
    }
}
