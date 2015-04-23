<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_4;

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
                'entityName' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm',
                'field' => 'createdAt',
                'value' => 'oro.ui.created_at',
                'replace' => 'oro.embeddedform.created_at.label'
            ],
            [
                'entityName' => 'Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm',
                'field' => 'updatedAt',
                'value' => 'oro.ui.updated_at',
                'replace' => 'oro.embeddedform.updated_at.label'
            ]
        ];

        foreach ($fields as $field) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(
                    $field['entityName'],
                    $field['field'],
                    'entity',
                    'label',
                    $field['value'],
                    $field['replace']
                )
            );
            $queries->addQuery(
                new UpdateEntityConfigIndexFieldValueQuery(
                    $field['entityName'],
                    $field['field'],
                    'entity',
                    'label',
                    $field['value'],
                    $field['replace']
                )
            );
        }
    }
}
