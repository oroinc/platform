<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateGroupOrganizationFieldConfig implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        //Set identity to false for importexport scope in organization field of Group entity
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\UserBundle\Entity\Group',
                'organization',
                'importexport',
                'identity',
                false
            )
        );
    }
}
