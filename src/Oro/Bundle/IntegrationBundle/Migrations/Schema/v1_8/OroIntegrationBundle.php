<?php

namespace Oro\Bundle\IntegrationBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\SecurityBundle\Migrations\Schema\SetOwnershipTypeQuery;

class OroIntegrationBundle implements Migration
{
    /**
     * Set ownership type for Integration entity to Organization
     *
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new SetOwnershipTypeQuery(
                'Oro\Bundle\IntegrationBundle\Entity\Channel',
                [
                    'owner_field_name' => 'organization',
                    'owner_column_name' => 'organization_id'
                ]
            )
        );
    }
}
