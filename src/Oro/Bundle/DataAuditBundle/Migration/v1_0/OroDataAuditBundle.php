<?php

namespace Oro\Bundle\DataAuditBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroDataAuditBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_audit (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id INT DEFAULT NULL, object_class VARCHAR(255) NOT NULL, object_name VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', INDEX IDX_5FBA427CA76ED395 (user_id), PRIMARY KEY(id))",
            "ALTER TABLE oro_audit ADD CONSTRAINT FK_5FBA427CA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE"
        ];
    }
}
