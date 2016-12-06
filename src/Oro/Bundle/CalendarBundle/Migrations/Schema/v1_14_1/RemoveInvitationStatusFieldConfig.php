<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_14_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveInvitationStatusFieldConfig implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveInvitationStatusFieldConfigQuery());
    }
}
