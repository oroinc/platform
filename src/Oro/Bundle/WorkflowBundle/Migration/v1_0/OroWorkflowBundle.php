<?php

namespace Oro\Bundle\WorkflowBundle\Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE oro_workflow_definition (name VARCHAR(255) NOT NULL, start_step_id INT DEFAULT NULL, `label` VARCHAR(255) NOT NULL, related_entity VARCHAR(255) NOT NULL, entity_attribute_name VARCHAR(255) NOT NULL, steps_display_ordered TINYINT(1) NOT NULL, enabled TINYINT(1) NOT NULL, configuration LONGTEXT NOT NULL COMMENT '(DC2Type:array)', UNIQUE INDEX UNIQ_6F737C36A2E43CCE (related_entity), INDEX IDX_6F737C368377424F (start_step_id), INDEX oro_workflow_definition_enabled_idx (enabled), PRIMARY KEY(name))",
            "CREATE TABLE oro_workflow_item (id INT AUTO_INCREMENT NOT NULL, workflow_name VARCHAR(255) NOT NULL, current_step_id INT DEFAULT NULL, entity_id INT DEFAULT NULL, closed TINYINT(1) NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, data LONGTEXT DEFAULT NULL, INDEX IDX_169789AED9BF9B19 (current_step_id), INDEX IDX_169789AE1BBC6E3D (workflow_name), PRIMARY KEY(id))",
            "CREATE TABLE oro_workflow_step (id INT AUTO_INCREMENT NOT NULL, workflow_name VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, `label` VARCHAR(255) NOT NULL, step_order INT NOT NULL, UNIQUE INDEX oro_workflow_step_unique_idx (workflow_name, name), INDEX IDX_4A35528C1BBC6E3D (workflow_name), PRIMARY KEY(id))",
            "CREATE TABLE oro_workflow_transition_log (id INT AUTO_INCREMENT NOT NULL, step_to_id INT DEFAULT NULL, workflow_item_id INT DEFAULT NULL, step_from_id INT DEFAULT NULL, transition VARCHAR(255) DEFAULT NULL, transition_date DATETIME NOT NULL, INDEX IDX_B3D57B301023C4EE (workflow_item_id), INDEX IDX_B3D57B30C8335FE4 (step_from_id), INDEX IDX_B3D57B302C17BD9A (step_to_id), PRIMARY KEY(id))",

            "ALTER TABLE oro_workflow_definition ADD CONSTRAINT FK_6F737C368377424F FOREIGN KEY (start_step_id) REFERENCES oro_workflow_step (id)",
            "ALTER TABLE oro_workflow_item ADD CONSTRAINT FK_169789AE1BBC6E3D FOREIGN KEY (workflow_name) REFERENCES oro_workflow_definition (name) ON DELETE CASCADE",
            "ALTER TABLE oro_workflow_item ADD CONSTRAINT FK_169789AED9BF9B19 FOREIGN KEY (current_step_id) REFERENCES oro_workflow_step (id)",
            "ALTER TABLE oro_workflow_step ADD CONSTRAINT FK_4A35528C1BBC6E3D FOREIGN KEY (workflow_name) REFERENCES oro_workflow_definition (name) ON DELETE CASCADE",
            "ALTER TABLE oro_workflow_transition_log ADD CONSTRAINT FK_B3D57B302C17BD9A FOREIGN KEY (step_to_id) REFERENCES oro_workflow_step (id)",
            "ALTER TABLE oro_workflow_transition_log ADD CONSTRAINT FK_B3D57B301023C4EE FOREIGN KEY (workflow_item_id) REFERENCES oro_workflow_item (id) ON DELETE CASCADE",
            "ALTER TABLE oro_workflow_transition_log ADD CONSTRAINT FK_B3D57B30C8335FE4 FOREIGN KEY (step_from_id) REFERENCES oro_workflow_step (id)"
        ];
    }
}
