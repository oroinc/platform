<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_5;

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
                'entityName' => 'Oro\Bundle\TagBundle\Entity\Tag',
                'field' => 'created',
                'value' => 'oro.ui.created_at',
                'replace' => 'oro.tag.created.label'
            ],
            [
                'entityName' => 'Oro\Bundle\TagBundle\Entity\Tagging',
                'field' => 'created',
                'value' => 'oro.ui.created_at',
                'replace' => 'oro.tag.tagging.created.label'
            ],
            [
                'entityName' => 'Oro\Bundle\TagBundle\Entity\Tag',
                'field' => 'updated',
                'value' => 'oro.ui.updated_at',
                'replace' => 'oro.tag.updated.label'
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
