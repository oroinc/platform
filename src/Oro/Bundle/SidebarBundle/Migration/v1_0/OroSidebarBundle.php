<?php

namespace Oro\Bundle\SidebarBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroSidebarBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_sidebar_state (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, position VARCHAR(13) NOT NULL, state VARCHAR(17) NOT NULL, UNIQUE INDEX sidebar_state_unique_idx (user_id, position), INDEX IDX_AB2BC195A76ED395 (user_id), PRIMARY KEY(id))",
            "CREATE TABLE oro_sidebar_widget (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, placement VARCHAR(50) NOT NULL, position SMALLINT NOT NULL, widget_name VARCHAR(50) NOT NULL, settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', state VARCHAR(22) NOT NULL, INDEX IDX_2FFBEA9CA76ED395 (user_id), INDEX sidebar_widgets_user_placement_idx (user_id, placement), INDEX sidebar_widgets_position_idx (position), PRIMARY KEY(id))",

            "ALTER TABLE oro_sidebar_state ADD CONSTRAINT FK_AB2BC195A76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE",
            "ALTER TABLE oro_sidebar_widget ADD CONSTRAINT FK_2FFBEA9CA76ED395 FOREIGN KEY (user_id) REFERENCES oro_user (id) ON DELETE CASCADE"
        ];
    }
}
