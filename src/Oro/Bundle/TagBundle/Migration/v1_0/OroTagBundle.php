<?php

namespace Oro\Bundle\TagBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroTagBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_tag_tag (id INT AUTO_INCREMENT NOT NULL, user_owner_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_CAF0DB575E237E06 (name), INDEX IDX_CAF0DB579EB185F9 (user_owner_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_tag_tagging (id INT AUTO_INCREMENT NOT NULL, user_owner_id INT DEFAULT NULL, tag_id INT DEFAULT NULL, created DATETIME NOT NULL, alias VARCHAR(100) NOT NULL, entity_name VARCHAR(100) NOT NULL, record_id INT NOT NULL, UNIQUE INDEX tagging_idx (tag_id, entity_name, record_id, user_owner_id), INDEX IDX_50107502BAD26311 (tag_id), INDEX IDX_501075029EB185F9 (user_owner_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_tag_tag ADD CONSTRAINT FK_CAF0DB579EB185F9 FOREIGN KEY (user_owner_id) REFERENCES oro_user (id) ON DELETE SET NULL",
            "ALTER TABLE oro_tag_tagging ADD CONSTRAINT FK_501075029EB185F9 FOREIGN KEY (user_owner_id) REFERENCES oro_user (id) ON DELETE SET NULL",
            "ALTER TABLE oro_tag_tagging ADD CONSTRAINT FK_50107502BAD26311 FOREIGN KEY (tag_id) REFERENCES oro_tag_tag (id) ON DELETE CASCADE"
        ];
    }
}
