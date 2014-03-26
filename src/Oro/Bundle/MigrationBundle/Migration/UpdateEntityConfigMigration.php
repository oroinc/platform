<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

class UpdateEntityConfigMigration implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /**
         * Cleanup config values
         */
        $queries->addQuery(
            'DELETE FROM oro_entity_config_value WHERE code = \'schema\' AND field_id IS NOT NULL;'
        );

        /**
         * Remove duplicates
         */
        $queries->addQuery(
            'DELETE FROM oro_entity_config_value WHERE field_id IN (
                SELECT id FROM oro_entity_config_field WHERE field_name LIKE \'field_%\'
            );
            DELETE FROM oro_entity_config_field WHERE field_name LIKE \'field_%\';'
        );

        /**
         * Remove broken OptionSet records
         */
        $queries->addQuery(
            'DELETE FROM oro_entity_config_value WHERE code = \'set_options\' AND value = \'Array\';'
        );

        /**
         * Remove configs for removed entity 'OroCRM\Bundle\ReportBundle\Entity\OpportunityByWorkflowItem'
         */
        $queries->addQuery(
            'DELETE FROM oro_entity_config_value
             WHERE
               field_id IN (
                 SELECT id FROM oro_entity_config_field WHERE entity_id = (
                   SELECT id FROM oro_entity_config
                   WHERE class_name = \'OroCRM\\Bundle\\ReportBundle\\Entity\\OpportunityByWorkflowItem\'
                 )
               )
               OR
               entity_id = (
                 SELECT id FROM oro_entity_config
                 WHERE class_name = \'OroCRM\\Bundle\\ReportBundle\\Entity\\OpportunityByWorkflowItem\'
               )'
        );
        $queries->addQuery(
            'DELETE FROM oro_entity_config_field WHERE entity_id = (
              SELECT id FROM oro_entity_config
              WHERE class_name = \'OroCRM\\Bundle\\ReportBundle\\Entity\\OpportunityByWorkflowItem\'
            )'
        );
        $queries->addQuery(
            'DELETE FROM oro_entity_config
             WHERE class_name = \'OroCRM\\Bundle\\ReportBundle\\Entity\\OpportunityByWorkflowItem\''
        );
    }
}
