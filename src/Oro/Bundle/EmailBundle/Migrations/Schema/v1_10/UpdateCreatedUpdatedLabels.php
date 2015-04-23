<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_10;

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
        $entityName = 'Oro\Bundle\EmailBundle\Entity\Email';

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                $entityName,
                'created',
                'entity',
                'label',
                'oro.ui.created_at',
                'oro.email.created.label'
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigIndexFieldValueQuery(
                $entityName,
                'created',
                'entity',
                'label',
                'oro.ui.created_at',
                'oro.email.created.label'
            )
        );
    }
}
