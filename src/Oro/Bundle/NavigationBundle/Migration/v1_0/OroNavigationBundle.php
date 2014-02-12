<?php

namespace Oro\Bundle\NavigationBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroNavigationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_navigation_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, url VARCHAR(1023) NOT NULL, title VARCHAR(255) NOT NULL, visited_at DATETIME NOT NULL, visit_count INT NOT NULL, INDEX IDX_B20613B9A76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_navigation_item (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, url VARCHAR(1023) NOT NULL, title VARCHAR(255) NOT NULL, position SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_323B0258A76ED395 (user_id), INDEX sorted_items_idx (user_id, position), PRIMARY KEY(id))",
            "CREATE TABLE oro_navigation_item_pinbar (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, maximized DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_54973433126F525E (item_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_navigation_pagestate (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, page_id VARCHAR(4000) NOT NULL, page_hash VARCHAR(32) NOT NULL, data LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8B43985B567C7E62 (page_hash), INDEX IDX_8B43985BA76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_navigation_title (id INT AUTO_INCREMENT NOT NULL, route VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, short_title VARCHAR(255) NOT NULL, is_system TINYINT(1) NOT NULL, UNIQUE INDEX unq_route (route), PRIMARY KEY(id))",

            "ALTER TABLE oro_navigation_history ADD CONSTRAINT FK_B20613B9A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_navigation_item ADD CONSTRAINT FK_323B0258A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_navigation_item_pinbar ADD CONSTRAINT FK_54973433126F525E FOREIGN KEY (item_id) REFERENCES oro_navigation_item (id) ON DELETE CASCADE",
            "ALTER TABLE oro_navigation_pagestate ADD CONSTRAINT FK_8B43985BA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE"
        ];
    }
}
