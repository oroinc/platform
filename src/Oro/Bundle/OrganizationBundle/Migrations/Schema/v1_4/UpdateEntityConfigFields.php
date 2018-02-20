<?php

namespace Oro\Bundle\OrganizationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityConfigFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new DropUnusedEntityConfigFieldValuesQuery());

        $fields = [
            [
                'entityName' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'field' => 'createdAt',
                'value' => 'oro.ui.created_at',
                'replace' => 'oro.organization.businessunit.created_at.label'
            ],
            [
                'entityName' => 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit',
                'field' => 'updatedAt',
                'value' => 'oro.ui.updated_at',
                'replace' => 'oro.organization.businessunit.updated_at.label'
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
