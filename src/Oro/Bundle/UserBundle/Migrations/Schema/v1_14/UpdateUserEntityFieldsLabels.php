<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateUserEntityFieldsLabels implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $fields = [
            [
                'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
                'field' => 'createdAt',
                'value' => 'oro.ui.created_at',
                'replace' => 'oro.user.created_at.label'
            ],
            [
                'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
                'field' => 'updatedAt',
                'value' => 'oro.ui.updated_at',
                'replace' => 'oro.user.updated_at.label'
            ],
            [
                'entityName' => 'Oro\Bundle\UserBundle\Entity\User',
                'field' => 'image',
                'value' => 'oro.user.avatar.label',
                'replace' => 'oro.user.image.label'
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
