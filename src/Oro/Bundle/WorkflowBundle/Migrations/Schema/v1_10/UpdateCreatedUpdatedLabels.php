<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateCreatedUpdatedLabels implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $fields = [
            [
                'entityName' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                'field' => 'createdAt',
                'value' => 'oro.ui.created_at'
            ],
            [
                'entityName' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                'field' => 'updatedAt',
                'value' => 'oro.ui.updated_at'
            ],
            [
                'entityName' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'field' => 'created',
                'value' => 'oro.ui.created_at'
            ],
            [
                'entityName' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem',
                'field' => 'updated',
                'value' => 'oro.ui.updated_at'
            ]
        ];

        foreach ($fields as $field) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(
                    $field['entityName'],
                    $field['field'],
                    'entity',
                    'label',
                    $field['value']
                )
            );
            $queries->addQuery(
                new UpdateEntityConfigIndexFieldValueQuery(
                    $field['entityName'],
                    $field['field'],
                    'entity',
                    'label',
                    $field['value']
                )
            );
        }
    }
}
